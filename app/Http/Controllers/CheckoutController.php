<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\Restaurant;
use App\Models\Coupon;
use App\Models\Dish;
use App\Models\GeoProvincia;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    private const CART_SESSION_KEY = 'shopping_cart';
    private const COUPON_SESSION_KEY = 'cart_coupon';
    private const CHECKOUT_SESSION_KEY = 'checkout_data';

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Muestra la pagina de checkout
     */
    public function index(Request $request): Response
    {
        $cart = $this->getCart();

        if (empty($cart['items'])) {
            return redirect()->route('cart.index')
                ->with('error', 'El carrito esta vacio');
        }

        $user = Auth::user();
        $checkoutData = Session::get(self::CHECKOUT_SESSION_KEY, []);

        // Obtener direcciones del usuario
        $addresses = $user->addresses()
            ->with(['provincia', 'municipio', 'sector'])
            ->orderByDesc('is_default')
            ->get();

        // Obtener metodos de pago del usuario
        $paymentMethods = $user->paymentMethods()
            ->where('is_active', true)
            ->get();

        // Obtener provincias para formulario de direccion
        $provincias = GeoProvincia::orderBy('name')->get();

        // Datos del restaurante
        $restaurant = Restaurant::find($cart['restaurant_id']);

        // Calcular totales
        $totals = $this->calculateTotals($cart);

        return Inertia::render('Checkout/Index', [
            'cart' => $cart,
            'totals' => $totals,
            'restaurant' => $restaurant,
            'addresses' => $addresses,
            'paymentMethods' => $paymentMethods,
            'provincias' => $provincias,
            'checkoutData' => $checkoutData,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'wallet_balance' => $user->wallet_balance,
            ],
        ]);
    }

    /**
     * Establece la direccion de entrega
     */
    public function setAddress(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'address_id' => 'required_without:new_address|nullable|integer|exists:user_addresses,id',
            'new_address' => 'required_without:address_id|nullable|array',
            'new_address.label' => 'required_with:new_address|string|max:50',
            'new_address.address_line_1' => 'required_with:new_address|string|max:255',
            'new_address.address_line_2' => 'nullable|string|max:255',
            'new_address.provincia_id' => 'required_with:new_address|integer|exists:geo_provincias,id',
            'new_address.municipio_id' => 'required_with:new_address|integer|exists:geo_municipios,id',
            'new_address.sector_id' => 'nullable|integer|exists:geo_sectores,id',
            'new_address.latitude' => 'nullable|numeric',
            'new_address.longitude' => 'nullable|numeric',
            'new_address.delivery_instructions' => 'nullable|string|max:500',
            'new_address.save_address' => 'nullable|boolean',
            'delivery_type' => 'required|string|in:delivery,pickup',
        ]);

        $user = Auth::user();
        $checkoutData = Session::get(self::CHECKOUT_SESSION_KEY, []);

        if ($validated['delivery_type'] === 'pickup') {
            $checkoutData['delivery_type'] = 'pickup';
            $checkoutData['address'] = null;
            Session::put(self::CHECKOUT_SESSION_KEY, $checkoutData);

            return response()->json([
                'success' => true,
                'message' => 'Opcion de recoger seleccionada',
                'delivery_type' => 'pickup',
            ]);
        }

        $addressData = null;

        if (!empty($validated['address_id'])) {
            // Usar direccion existente
            $address = $user->addresses()->findOrFail($validated['address_id']);
            $addressData = [
                'id' => $address->id,
                'label' => $address->label,
                'address_line_1' => $address->address_line_1,
                'address_line_2' => $address->address_line_2,
                'provincia' => $address->provincia->name,
                'municipio' => $address->municipio->name,
                'sector' => $address->sector?->name,
                'latitude' => $address->latitude,
                'longitude' => $address->longitude,
                'delivery_instructions' => $address->delivery_instructions,
            ];
        } else {
            // Nueva direccion
            $newAddress = $validated['new_address'];

            // Guardar si el usuario lo solicita
            if (!empty($newAddress['save_address'])) {
                $savedAddress = $user->addresses()->create([
                    'label' => $newAddress['label'],
                    'address_line_1' => $newAddress['address_line_1'],
                    'address_line_2' => $newAddress['address_line_2'] ?? null,
                    'provincia_id' => $newAddress['provincia_id'],
                    'municipio_id' => $newAddress['municipio_id'],
                    'sector_id' => $newAddress['sector_id'] ?? null,
                    'latitude' => $newAddress['latitude'] ?? null,
                    'longitude' => $newAddress['longitude'] ?? null,
                    'delivery_instructions' => $newAddress['delivery_instructions'] ?? null,
                ]);
                $savedAddress->load(['provincia', 'municipio', 'sector']);

                $addressData = [
                    'id' => $savedAddress->id,
                    'label' => $savedAddress->label,
                    'address_line_1' => $savedAddress->address_line_1,
                    'address_line_2' => $savedAddress->address_line_2,
                    'provincia' => $savedAddress->provincia->name,
                    'municipio' => $savedAddress->municipio->name,
                    'sector' => $savedAddress->sector?->name,
                    'latitude' => $savedAddress->latitude,
                    'longitude' => $savedAddress->longitude,
                    'delivery_instructions' => $savedAddress->delivery_instructions,
                ];
            } else {
                // Obtener nombres de ubicacion
                $provincia = GeoProvincia::find($newAddress['provincia_id']);

                $addressData = [
                    'label' => $newAddress['label'],
                    'address_line_1' => $newAddress['address_line_1'],
                    'address_line_2' => $newAddress['address_line_2'] ?? null,
                    'provincia' => $provincia?->name,
                    'municipio' => \App\Models\GeoMunicipio::find($newAddress['municipio_id'])?->name,
                    'sector' => isset($newAddress['sector_id']) ? \App\Models\GeoSector::find($newAddress['sector_id'])?->name : null,
                    'latitude' => $newAddress['latitude'] ?? null,
                    'longitude' => $newAddress['longitude'] ?? null,
                    'delivery_instructions' => $newAddress['delivery_instructions'] ?? null,
                ];
            }
        }

        $checkoutData['delivery_type'] = 'delivery';
        $checkoutData['address'] = $addressData;
        Session::put(self::CHECKOUT_SESSION_KEY, $checkoutData);

        return response()->json([
            'success' => true,
            'message' => 'Direccion de entrega establecida',
            'address' => $addressData,
            'delivery_type' => 'delivery',
        ]);
    }

    /**
     * Establece el metodo de pago
     */
    public function setPaymentMethod(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_method' => 'required|string|in:cash,card,wallet,transfer',
            'payment_method_id' => 'nullable|integer|exists:payment_methods,id',
            'tip' => 'nullable|numeric|min:0|max:9999',
        ]);

        $user = Auth::user();
        $cart = $this->getCart();
        $totals = $this->calculateTotals($cart);

        // Verificar saldo de wallet si es necesario
        if ($validated['payment_method'] === 'wallet') {
            if ($user->wallet_balance < $totals['total']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Saldo insuficiente en tu wallet. Saldo actual: RD$ ' . number_format($user->wallet_balance, 2),
                ], 422);
            }
        }

        // Verificar tarjeta si es necesario
        if ($validated['payment_method'] === 'card' && !empty($validated['payment_method_id'])) {
            $paymentMethod = $user->paymentMethods()
                ->where('id', $validated['payment_method_id'])
                ->where('is_active', true)
                ->first();

            if (!$paymentMethod) {
                return response()->json([
                    'success' => false,
                    'message' => 'Metodo de pago no valido',
                ], 422);
            }
        }

        $checkoutData = Session::get(self::CHECKOUT_SESSION_KEY, []);
        $checkoutData['payment_method'] = $validated['payment_method'];
        $checkoutData['payment_method_id'] = $validated['payment_method_id'] ?? null;
        $checkoutData['tip'] = $validated['tip'] ?? 0;
        Session::put(self::CHECKOUT_SESSION_KEY, $checkoutData);

        return response()->json([
            'success' => true,
            'message' => 'Metodo de pago establecido',
            'payment_method' => $validated['payment_method'],
        ]);
    }

    /**
     * Confirma y crea la orden
     */
    public function confirm(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_notes' => 'nullable|string|max:500',
            'scheduled_for' => 'nullable|date|after:now',
        ]);

        $user = Auth::user();
        $cart = $this->getCart();
        $checkoutData = Session::get(self::CHECKOUT_SESSION_KEY, []);
        $coupon = Session::get(self::COUPON_SESSION_KEY);

        // Validaciones
        if (empty($cart['items'])) {
            return response()->json([
                'success' => false,
                'message' => 'El carrito esta vacio',
            ], 422);
        }

        $deliveryType = $checkoutData['delivery_type'] ?? null;
        if (!$deliveryType) {
            return response()->json([
                'success' => false,
                'message' => 'Selecciona un tipo de entrega',
            ], 422);
        }

        if ($deliveryType === 'delivery' && empty($checkoutData['address'])) {
            return response()->json([
                'success' => false,
                'message' => 'Selecciona una direccion de entrega',
            ], 422);
        }

        $paymentMethod = $checkoutData['payment_method'] ?? null;
        if (!$paymentMethod) {
            return response()->json([
                'success' => false,
                'message' => 'Selecciona un metodo de pago',
            ], 422);
        }

        $restaurant = Restaurant::find($cart['restaurant_id']);
        if (!$restaurant || !$restaurant->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'El restaurante no esta disponible',
            ], 422);
        }

        $totals = $this->calculateTotals($cart, $checkoutData['tip'] ?? 0);

        // Verificar minimo de orden
        if ($totals['subtotal'] < $cart['minimum_order']) {
            return response()->json([
                'success' => false,
                'message' => 'El pedido minimo es RD$ ' . number_format($cart['minimum_order'], 2),
            ], 422);
        }

        // Verificar saldo de wallet
        if ($paymentMethod === 'wallet' && $user->wallet_balance < $totals['total']) {
            return response()->json([
                'success' => false,
                'message' => 'Saldo insuficiente en tu wallet',
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Obtener estado inicial
            $pendingStatus = OrderStatus::where('slug', 'pending')->firstOrFail();

            // Crear la orden
            $order = Order::create([
                'user_id' => $user->id,
                'restaurant_id' => $restaurant->id,
                'status_id' => $pendingStatus->id,
                'type' => $deliveryType,
                'delivery_address' => $deliveryType === 'delivery' ? $checkoutData['address'] : null,
                'delivery_latitude' => $checkoutData['address']['latitude'] ?? null,
                'delivery_longitude' => $checkoutData['address']['longitude'] ?? null,
                'delivery_instructions' => $checkoutData['address']['delivery_instructions'] ?? null,
                'subtotal' => $totals['subtotal'],
                'tax' => $totals['tax'],
                'delivery_fee' => $deliveryType === 'delivery' ? $totals['delivery_fee'] : 0,
                'service_fee' => $totals['service_fee'] ?? 0,
                'tip' => $totals['tip'],
                'discount' => $totals['discount'],
                'total' => $totals['total'],
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentMethod === 'cash' ? 'pending' : 'pending',
                'coupon_id' => $coupon['id'] ?? null,
                'coupon_code' => $coupon['code'] ?? null,
                'estimated_preparation_time' => $restaurant->preparation_time ?? 30,
                'scheduled_for' => $validated['scheduled_for'] ?? null,
                'customer_notes' => $validated['customer_notes'] ?? null,
            ]);

            // Crear items de la orden
            foreach ($cart['items'] as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'dish_id' => $item['dish_id'],
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'base_price' => $item['base_price'],
                    'options_price' => $item['options_price'],
                    'subtotal' => $item['unit_price'] * $item['quantity'],
                    'options' => $item['options'],
                    'special_instructions' => $item['special_instructions'],
                ]);

                // Incrementar contador de ordenes del plato
                Dish::find($item['dish_id'])?->incrementOrders();
            }

            // Registrar historial de estado
            $order->statusHistory()->create([
                'status_id' => $pendingStatus->id,
                'notes' => 'Orden creada',
            ]);

            // Procesar pago con wallet
            if ($paymentMethod === 'wallet') {
                $user->deductFromWallet($totals['total'], 'Pago de orden #' . $order->order_number, $order->id);
                $order->update([
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                ]);
            }

            // Registrar uso del cupon
            if ($coupon) {
                $couponModel = Coupon::find($coupon['id']);
                $couponModel?->recordUsage($user->id, $order->id);
            }

            // Agregar puntos de lealtad (1 punto por cada RD$100)
            $loyaltyPoints = (int) floor($totals['total'] / 100);
            if ($loyaltyPoints > 0) {
                $user->addLoyaltyPoints($loyaltyPoints, 'Puntos por orden #' . $order->order_number, $order->id);
            }

            // Incrementar contador de ordenes del restaurante
            $restaurant->increment('total_orders');

            DB::commit();

            // Limpiar sesiones
            Session::forget(self::CART_SESSION_KEY);
            Session::forget(self::COUPON_SESSION_KEY);
            Session::forget(self::CHECKOUT_SESSION_KEY);

            return response()->json([
                'success' => true,
                'message' => 'Orden creada exitosamente',
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'total' => $order->total,
                ],
                'redirect_url' => route('checkout.success', $order->order_number),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la orden. Por favor intenta de nuevo.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Muestra la pagina de confirmacion exitosa
     */
    public function success(Request $request, string $orderNumber): Response
    {
        $user = Auth::user();

        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', $user->id)
            ->with(['restaurant', 'items.dish', 'status'])
            ->firstOrFail();

        return Inertia::render('Checkout/Success', [
            'order' => $order,
        ]);
    }

    /**
     * Obtiene el carrito de la sesion
     */
    private function getCart(): array
    {
        return Session::get(self::CART_SESSION_KEY, [
            'restaurant_id' => null,
            'restaurant_name' => null,
            'restaurant_slug' => null,
            'minimum_order' => 0,
            'delivery_fee' => 0,
            'items' => [],
        ]);
    }

    /**
     * Calcula los totales del carrito
     */
    private function calculateTotals(array $cart, float $tip = 0): array
    {
        $items = $cart['items'] ?? [];

        $subtotal = array_reduce($items, function ($carry, $item) {
            return $carry + ($item['unit_price'] * $item['quantity']);
        }, 0);

        $coupon = Session::get(self::COUPON_SESSION_KEY);
        $discount = $coupon['discount'] ?? 0;

        $tax = ($subtotal - $discount) * 0.18;
        $deliveryFee = $cart['delivery_fee'] ?? 0;
        $serviceFee = $subtotal * 0.05; // 5% fee de servicio

        $total = $subtotal + $tax + $deliveryFee + $serviceFee + $tip - $discount;

        return [
            'subtotal' => round($subtotal, 2),
            'tax' => round($tax, 2),
            'delivery_fee' => round($deliveryFee, 2),
            'service_fee' => round($serviceFee, 2),
            'tip' => round($tip, 2),
            'discount' => round($discount, 2),
            'total' => round(max(0, $total), 2),
        ];
    }
}
