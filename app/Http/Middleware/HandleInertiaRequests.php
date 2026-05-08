<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'avatar' => $request->user()->avatar,
                    'role' => $request->user()->role,
                    'wallet_balance' => $request->user()->wallet_balance,
                    'loyalty_points' => $request->user()->loyalty_points,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
            'cart' => fn () => $this->getCartData($request),
            'config' => [
                'app_name' => config('app.name'),
                'currency' => 'RD$',
                'currency_code' => 'DOP',
                'tax_rate' => config('sazonrd.orders.tax_rate'),
            ],
        ]);
    }

    /**
     * Get cart data for the current user/session.
     */
    protected function getCartData(Request $request): ?array
    {
        $cart = null;

        if ($request->user()) {
            $cart = $request->user()->cart()->with(['items.dish', 'items.combo', 'restaurant'])->first();
        } else {
            $sessionId = $request->session()->getId();
            $cart = \App\Models\Cart::where('session_id', $sessionId)
                ->with(['items.dish', 'items.combo', 'restaurant'])
                ->first();
        }

        if (!$cart) {
            return null;
        }

        return [
            'id' => $cart->id,
            'restaurant' => $cart->restaurant ? [
                'id' => $cart->restaurant->id,
                'name' => $cart->restaurant->name,
                'slug' => $cart->restaurant->slug,
                'logo' => $cart->restaurant->logo_url,
                'minimum_order' => $cart->restaurant->minimum_order,
            ] : null,
            'items_count' => $cart->items->sum('quantity'),
            'subtotal' => $cart->subtotal,
            'discount' => $cart->discount,
            'coupon_code' => $cart->coupon?->code,
        ];
    }
}
