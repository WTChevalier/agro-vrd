<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\RestaurantReview;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Lista las ordenes del usuario
     */
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'status' => 'nullable|string|in:active,completed,cancelled,all',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'per_page' => 'nullable|integer|min:5|max:50',
        ]);

        $user = Auth::user();
        $status = $validated['status'] ?? 'all';

        $query = Order::where('user_id', $user->id)
            ->with(['restaurant:id,name,slug,logo', 'status', 'items']);

        // Filtrar por estado
        match ($status) {
            'active' => $query->active(),
            'completed' => $query->completed(),
            'cancelled' => $query->cancelled(),
            default => null,
        };

        // Filtrar por fecha
        if (!empty($validated['from_date'])) {
            $query->whereDate('created_at', '>=', $validated['from_date']);
        }

        if (!empty($validated['to_date'])) {
            $query->whereDate('created_at', '<=', $validated['to_date']);
        }

        $perPage = $validated['per_page'] ?? 15;
        $orders = $query->latest()->paginate($perPage)->withQueryString();

        // Obtener estadisticas
        $stats = [
            'total_orders' => $user->orders()->count(),
            'active_orders' => $user->orders()->active()->count(),
            'completed_orders' => $user->orders()->completed()->count(),
            'total_spent' => $user->orders()->completed()->sum('total'),
        ];

        return Inertia::render('Order/Index', [
            'orders' => $orders,
            'filters' => $validated,
            'stats' => $stats,
        ]);
    }

    /**
     * Muestra los detalles de una orden
     */
    public function show(Request $request, string $orderNumber): Response
    {
        $user = Auth::user();

        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', $user->id)
            ->with([
                'restaurant:id,name,slug,logo,phone,whatsapp,address',
                'status',
                'items.dish:id,name,image',
                'statusHistory' => function ($query) {
                    $query->with('status')->latest()->limit(10);
                },
                'deliveryDriver:id,name,phone',
                'coupon:id,code,name',
                'restaurantReview',
            ])
            ->firstOrFail();

        return Inertia::render('Order/Show', [
            'order' => $order,
            'canCancel' => $order->can_be_cancelled,
            'canRate' => $order->delivered_at && !$order->restaurantReview,
        ]);
    }

    /**
     * Muestra el tracking en tiempo real de una orden
     */
    public function tracking(Request $request, string $orderNumber): Response|JsonResponse
    {
        $user = Auth::user();

        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', $user->id)
            ->with([
                'restaurant:id,name,slug,logo,phone,latitude,longitude',
                'status',
                'statusHistory' => function ($query) {
                    $query->with('status')->latest();
                },
                'tracking' => function ($query) {
                    $query->latest()->limit(50);
                },
                'deliveryDriver:id,name,phone',
            ])
            ->firstOrFail();

        // Calcular tiempo estimado restante
        $estimatedRemaining = null;
        if (!$order->delivered_at && !$order->cancelled_at) {
            $totalTime = ($order->estimated_preparation_time ?? 30) + ($order->estimated_delivery_time ?? 20);
            $elapsed = $order->created_at->diffInMinutes(now());
            $estimatedRemaining = max(0, $totalTime - $elapsed);
        }

        // Preparar datos de tracking
        $trackingData = [
            'order' => $order,
            'estimated_remaining_minutes' => $estimatedRemaining,
            'estimated_arrival' => $order->estimated_arrival,
            'current_location' => $order->tracking->first()?->only(['latitude', 'longitude']),
            'delivery_location' => [
                'latitude' => $order->delivery_latitude,
                'longitude' => $order->delivery_longitude,
            ],
            'restaurant_location' => [
                'latitude' => $order->restaurant->latitude,
                'longitude' => $order->restaurant->longitude,
            ],
        ];

        if ($request->wantsJson()) {
            return response()->json($trackingData);
        }

        return Inertia::render('Order/Tracking', $trackingData);
    }

    /**
     * Cancela una orden
     */
    public function cancel(Request $request, string $orderNumber): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $user = Auth::user();

        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if (!$order->can_be_cancelled) {
            return response()->json([
                'success' => false,
                'message' => 'Esta orden no puede ser cancelada en su estado actual',
            ], 422);
        }

        $order->cancel($validated['reason'], 'customer');

        // Si el pago fue con wallet, reembolsar
        if ($order->payment_method === 'wallet' && $order->payment_status === 'paid') {
            $user->addToWallet(
                $order->total,
                'Reembolso por cancelacion de orden #' . $order->order_number,
                $order->id
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Orden cancelada exitosamente',
            'order' => $order->fresh(['status']),
        ]);
    }

    /**
     * Re-ordena los mismos items de una orden anterior
     */
    public function reorder(Request $request, string $orderNumber): JsonResponse
    {
        $user = Auth::user();

        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', $user->id)
            ->with(['items', 'restaurant'])
            ->firstOrFail();

        // Verificar que el restaurante esta activo
        if (!$order->restaurant->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'El restaurante no esta disponible actualmente',
            ], 422);
        }

        // Preparar items para el carrito
        $cartItems = [];
        $unavailableItems = [];

        foreach ($order->items as $item) {
            $dish = $item->dish;

            if (!$dish || !$dish->isOrderable()) {
                $unavailableItems[] = $item->name;
                continue;
            }

            $cartItems[] = [
                'id' => uniqid('item_'),
                'dish_id' => $dish->id,
                'name' => $dish->name,
                'image' => $dish->image_url,
                'base_price' => (float) $dish->price,
                'options_price' => (float) $item->options_price,
                'unit_price' => (float) $dish->price + (float) $item->options_price,
                'quantity' => $item->quantity,
                'options' => $item->options ?? [],
                'special_instructions' => $item->special_instructions,
            ];
        }

        if (empty($cartItems)) {
            return response()->json([
                'success' => false,
                'message' => 'Ninguno de los platos de esta orden esta disponible actualmente',
            ], 422);
        }

        // Guardar en sesion
        $cart = [
            'restaurant_id' => $order->restaurant_id,
            'restaurant_name' => $order->restaurant->name,
            'restaurant_slug' => $order->restaurant->slug,
            'minimum_order' => (float) $order->restaurant->minimum_order,
            'delivery_fee' => (float) $order->restaurant->delivery_fee,
            'items' => $cartItems,
        ];

        Session::put('shopping_cart', $cart);

        $response = [
            'success' => true,
            'message' => 'Items agregados al carrito',
            'redirect_url' => route('cart.index'),
        ];

        if (!empty($unavailableItems)) {
            $response['warning'] = 'Algunos items no estan disponibles: ' . implode(', ', $unavailableItems);
        }

        return response()->json($response);
    }

    /**
     * Califica una orden completada
     */
    public function rate(Request $request, string $orderNumber): JsonResponse
    {
        $validated = $request->validate([
            'overall_rating' => 'required|integer|min:1|max:5',
            'food_rating' => 'nullable|integer|min:1|max:5',
            'delivery_rating' => 'nullable|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'images' => 'nullable|array|max:5',
            'images.*' => 'string', // URLs de imagenes subidas
        ]);

        $user = Auth::user();

        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', $user->id)
            ->with('restaurant')
            ->firstOrFail();

        // Verificar que la orden esta completada
        if (!$order->delivered_at) {
            return response()->json([
                'success' => false,
                'message' => 'Solo puedes calificar ordenes completadas',
            ], 422);
        }

        // Verificar que no ha sido calificada
        if ($order->restaurantReview) {
            return response()->json([
                'success' => false,
                'message' => 'Ya has calificado esta orden',
            ], 422);
        }

        // Crear review
        $review = RestaurantReview::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'restaurant_id' => $order->restaurant_id,
            'overall_rating' => $validated['overall_rating'],
            'food_rating' => $validated['food_rating'] ?? $validated['overall_rating'],
            'delivery_rating' => $validated['delivery_rating'],
            'comment' => $validated['comment'],
            'images' => $validated['images'] ?? [],
            'status' => 'pending', // Requiere aprobacion
        ]);

        // Actualizar orden
        $order->update([
            'rating' => $validated['overall_rating'],
            'review' => $validated['comment'],
            'reviewed_at' => now(),
        ]);

        // Agregar puntos de lealtad por calificar
        $user->addLoyaltyPoints(10, 'Puntos por calificar orden #' . $order->order_number, $order->id);

        return response()->json([
            'success' => true,
            'message' => 'Gracias por tu calificacion',
            'review' => $review,
            'loyalty_points_earned' => 10,
        ]);
    }
}
