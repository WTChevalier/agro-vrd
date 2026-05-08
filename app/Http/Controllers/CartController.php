<?php

namespace App\Http\Controllers;

use App\Models\Dish;
use App\Models\Restaurant;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    private const CART_SESSION_KEY = 'shopping_cart';
    private const COUPON_SESSION_KEY = 'cart_coupon';

    /**
     * Muestra el contenido del carrito
     */
    public function index(Request $request): Response|JsonResponse
    {
        $cart = $this->getCart();
        $cartData = $this->prepareCartData($cart);

        if ($request->wantsJson()) {
            return response()->json($cartData);
        }

        return Inertia::render('Cart/Index', $cartData);
    }

    /**
     * Agrega un item al carrito
     */
    public function add(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'dish_id' => 'required|integer|exists:dishes,id',
            'quantity' => 'required|integer|min:1|max:99',
            'options' => 'nullable|array',
            'options.*' => 'integer|exists:dish_options,id',
            'special_instructions' => 'nullable|string|max:500',
        ]);

        $dish = Dish::with(['restaurant', 'optionGroups.options'])
            ->findOrFail($validated['dish_id']);

        // Verificar que el plato esta disponible
        if (!$dish->isOrderable()) {
            return response()->json([
                'success' => false,
                'message' => 'Este plato no esta disponible en este momento',
            ], 422);
        }

        $cart = $this->getCart();

        // Verificar si el carrito ya tiene items de otro restaurante
        if (!empty($cart['items']) && $cart['restaurant_id'] !== $dish->restaurant_id) {
            return response()->json([
                'success' => false,
                'message' => 'Ya tienes items de otro restaurante en tu carrito. Deseas vaciar el carrito?',
                'requires_confirmation' => true,
                'current_restaurant' => $cart['restaurant_name'],
                'new_restaurant' => $dish->restaurant->name,
            ], 409);
        }

        // Calcular precio con opciones
        $selectedOptions = $validated['options'] ?? [];
        $optionsPrice = 0;
        $optionsDetails = [];

        foreach ($dish->optionGroups as $group) {
            foreach ($group->options as $option) {
                if (in_array($option->id, $selectedOptions)) {
                    $optionsPrice += $option->price_adjustment;
                    $optionsDetails[] = [
                        'id' => $option->id,
                        'group_name' => $group->name,
                        'name' => $option->name,
                        'price' => $option->price_adjustment,
                    ];
                }
            }
        }

        // Crear item del carrito
        $cartItem = [
            'id' => uniqid('item_'),
            'dish_id' => $dish->id,
            'name' => $dish->name,
            'image' => $dish->image_url,
            'base_price' => (float) $dish->price,
            'options_price' => $optionsPrice,
            'unit_price' => (float) $dish->price + $optionsPrice,
            'quantity' => $validated['quantity'],
            'options' => $optionsDetails,
            'special_instructions' => $validated['special_instructions'] ?? null,
        ];

        // Actualizar carrito
        $cart['restaurant_id'] = $dish->restaurant_id;
        $cart['restaurant_name'] = $dish->restaurant->name;
        $cart['restaurant_slug'] = $dish->restaurant->slug;
        $cart['minimum_order'] = (float) $dish->restaurant->minimum_order;
        $cart['delivery_fee'] = (float) $dish->restaurant->delivery_fee;
        $cart['items'][] = $cartItem;

        $this->saveCart($cart);

        return response()->json([
            'success' => true,
            'message' => 'Plato agregado al carrito',
            'cart' => $this->prepareCartData($cart),
        ]);
    }

    /**
     * Actualiza la cantidad de un item en el carrito
     */
    public function update(Request $request, string $itemId): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:99',
        ]);

        $cart = $this->getCart();
        $itemFound = false;

        foreach ($cart['items'] as &$item) {
            if ($item['id'] === $itemId) {
                $item['quantity'] = $validated['quantity'];
                $itemFound = true;
                break;
            }
        }

        if (!$itemFound) {
            return response()->json([
                'success' => false,
                'message' => 'Item no encontrado en el carrito',
            ], 404);
        }

        $this->saveCart($cart);

        return response()->json([
            'success' => true,
            'message' => 'Cantidad actualizada',
            'cart' => $this->prepareCartData($cart),
        ]);
    }

    /**
     * Elimina un item del carrito
     */
    public function remove(Request $request, string $itemId): JsonResponse
    {
        $cart = $this->getCart();

        $initialCount = count($cart['items']);
        $cart['items'] = array_values(array_filter(
            $cart['items'],
            fn($item) => $item['id'] !== $itemId
        ));

        if (count($cart['items']) === $initialCount) {
            return response()->json([
                'success' => false,
                'message' => 'Item no encontrado en el carrito',
            ], 404);
        }

        // Si el carrito queda vacio, limpiarlo completamente
        if (empty($cart['items'])) {
            $this->clearCartData();
            return response()->json([
                'success' => true,
                'message' => 'Item eliminado. El carrito esta vacio',
                'cart' => $this->prepareCartData($this->getCart()),
            ]);
        }

        $this->saveCart($cart);

        return response()->json([
            'success' => true,
            'message' => 'Item eliminado del carrito',
            'cart' => $this->prepareCartData($cart),
        ]);
    }

    /**
     * Vacia el carrito completamente
     */
    public function clear(Request $request): JsonResponse
    {
        $this->clearCartData();

        return response()->json([
            'success' => true,
            'message' => 'Carrito vaciado',
            'cart' => $this->prepareCartData($this->getCart()),
        ]);
    }

    /**
     * Aplica un cupon al carrito
     */
    public function applyCoupon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
        ]);

        $cart = $this->getCart();

        if (empty($cart['items'])) {
            return response()->json([
                'success' => false,
                'message' => 'El carrito esta vacio',
            ], 422);
        }

        $coupon = Coupon::byCode($validated['code'])->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Cupon no encontrado',
            ], 404);
        }

        // Calcular subtotal
        $subtotal = array_reduce($cart['items'], function ($carry, $item) {
            return $carry + ($item['unit_price'] * $item['quantity']);
        }, 0);

        // Verificar si el usuario esta autenticado para cupones con restricciones de usuario
        $user = Auth::user();
        if ($user) {
            $errors = $coupon->getValidationErrors($user, $subtotal, $cart['restaurant_id']);
            if (!empty($errors)) {
                return response()->json([
                    'success' => false,
                    'message' => $errors[0],
                    'errors' => $errors,
                ], 422);
            }
        } else {
            // Validacion basica sin usuario
            if (!$coupon->is_valid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este cupon no es valido',
                ], 422);
            }

            if (!$coupon->canBeUsedNow()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este cupon no es valido en este momento',
                ], 422);
            }

            if (!$coupon->canBeAppliedToOrder($subtotal, $cart['restaurant_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => "El pedido minimo para este cupon es RD$ " . number_format($coupon->min_order_amount, 2),
                ], 422);
            }
        }

        // Calcular descuento
        $deliveryFee = $cart['delivery_fee'] ?? 0;
        $discount = $coupon->calculateDiscount($subtotal, $deliveryFee);

        // Guardar cupon en sesion
        Session::put(self::COUPON_SESSION_KEY, [
            'id' => $coupon->id,
            'code' => $coupon->code,
            'name' => $coupon->name,
            'type' => $coupon->type,
            'value' => $coupon->value,
            'discount' => $discount,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cupon aplicado correctamente',
            'coupon' => [
                'code' => $coupon->code,
                'name' => $coupon->name,
                'discount' => $discount,
                'formatted_discount' => 'RD$ ' . number_format($discount, 2),
            ],
            'cart' => $this->prepareCartData($cart),
        ]);
    }

    /**
     * Elimina el cupon del carrito
     */
    public function removeCoupon(Request $request): JsonResponse
    {
        Session::forget(self::COUPON_SESSION_KEY);

        return response()->json([
            'success' => true,
            'message' => 'Cupon eliminado',
            'cart' => $this->prepareCartData($this->getCart()),
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
     * Guarda el carrito en la sesion
     */
    private function saveCart(array $cart): void
    {
        Session::put(self::CART_SESSION_KEY, $cart);
    }

    /**
     * Limpia completamente los datos del carrito
     */
    private function clearCartData(): void
    {
        Session::forget(self::CART_SESSION_KEY);
        Session::forget(self::COUPON_SESSION_KEY);
    }

    /**
     * Prepara los datos del carrito para la respuesta
     */
    private function prepareCartData(array $cart): array
    {
        $items = $cart['items'] ?? [];

        // Calcular subtotal
        $subtotal = array_reduce($items, function ($carry, $item) {
            return $carry + ($item['unit_price'] * $item['quantity']);
        }, 0);

        // Obtener cupon si existe
        $coupon = Session::get(self::COUPON_SESSION_KEY);
        $discount = $coupon['discount'] ?? 0;

        // Calcular ITBIS (18%)
        $tax = ($subtotal - $discount) * 0.18;

        // Delivery fee
        $deliveryFee = $cart['delivery_fee'] ?? 0;

        // Total
        $total = $subtotal + $tax + $deliveryFee - $discount;

        // Verificar minimo de orden
        $minimumOrder = $cart['minimum_order'] ?? 0;
        $meetsMinimum = $subtotal >= $minimumOrder;

        return [
            'restaurant' => [
                'id' => $cart['restaurant_id'],
                'name' => $cart['restaurant_name'],
                'slug' => $cart['restaurant_slug'],
                'minimum_order' => $minimumOrder,
                'delivery_fee' => $deliveryFee,
            ],
            'items' => $items,
            'items_count' => array_reduce($items, fn($c, $i) => $c + $i['quantity'], 0),
            'subtotal' => round($subtotal, 2),
            'tax' => round($tax, 2),
            'delivery_fee' => round($deliveryFee, 2),
            'discount' => round($discount, 2),
            'total' => round(max(0, $total), 2),
            'coupon' => $coupon,
            'meets_minimum' => $meetsMinimum,
            'minimum_remaining' => $meetsMinimum ? 0 : round($minimumOrder - $subtotal, 2),
            'formatted' => [
                'subtotal' => 'RD$ ' . number_format($subtotal, 2),
                'tax' => 'RD$ ' . number_format($tax, 2),
                'delivery_fee' => 'RD$ ' . number_format($deliveryFee, 2),
                'discount' => 'RD$ ' . number_format($discount, 2),
                'total' => 'RD$ ' . number_format(max(0, $total), 2),
            ],
        ];
    }
}
