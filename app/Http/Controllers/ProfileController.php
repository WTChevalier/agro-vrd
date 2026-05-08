<?php

namespace App\Http\Controllers;

use App\Models\UserAddress;
use App\Models\GeoProvincia;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Muestra el perfil del usuario
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();

        $user->load([
            'addresses' => function ($query) {
                $query->with(['provincia', 'municipio', 'sector'])
                    ->orderByDesc('is_default');
            },
        ]);

        // Estadisticas del usuario
        $stats = [
            'total_orders' => $user->orders()->count(),
            'completed_orders' => $user->orders()->completed()->count(),
            'total_spent' => $user->orders()->completed()->sum('total'),
            'favorites_count' => $user->favorites()->count(),
            'reviews_count' => $user->reviews()->count(),
        ];

        // Tier de lealtad actual
        $loyaltyTier = $user->getCurrentLoyaltyTier();

        return Inertia::render('Profile/Index', [
            'user' => $user,
            'stats' => $stats,
            'loyaltyTier' => $loyaltyTier,
        ]);
    }

    /**
     * Actualiza la informacion del perfil
     */
    public function update(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'current_password' => 'nullable|required_with:new_password|string',
            'new_password' => ['nullable', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        // Verificar password actual si se quiere cambiar
        if (!empty($validated['current_password'])) {
            if (!Hash::check($validated['current_password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La contrasena actual es incorrecta',
                    'errors' => ['current_password' => ['La contrasena actual es incorrecta']],
                ], 422);
            }
        }

        // Actualizar datos basicos
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'];

        // Subir avatar si se proporciona
        if ($request->hasFile('avatar')) {
            // Eliminar avatar anterior
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
        }

        // Cambiar password si se proporciona
        if (!empty($validated['new_password'])) {
            $user->password = Hash::make($validated['new_password']);
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado exitosamente',
            'user' => $user->only(['id', 'name', 'email', 'phone', 'avatar']),
        ]);
    }

    /**
     * Muestra las direcciones del usuario
     */
    public function addresses(Request $request): Response|JsonResponse
    {
        $user = Auth::user();

        $addresses = $user->addresses()
            ->with(['provincia', 'municipio', 'sector'])
            ->orderByDesc('is_default')
            ->get();

        $provincias = GeoProvincia::orderBy('name')->get();

        if ($request->wantsJson()) {
            return response()->json([
                'addresses' => $addresses,
            ]);
        }

        return Inertia::render('Profile/Addresses', [
            'addresses' => $addresses,
            'provincias' => $provincias,
        ]);
    }

    /**
     * Guarda una nueva direccion
     */
    public function storeAddress(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'label' => 'required|string|max:50',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'provincia_id' => 'required|integer|exists:geo_provincias,id',
            'municipio_id' => 'required|integer|exists:geo_municipios,id',
            'sector_id' => 'nullable|integer|exists:geo_sectores,id',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'delivery_instructions' => 'nullable|string|max:500',
            'is_default' => 'nullable|boolean',
        ]);

        $user = Auth::user();

        // Si es default, quitar default de otras direcciones
        if (!empty($validated['is_default'])) {
            $user->addresses()->update(['is_default' => false]);
        }

        $address = $user->addresses()->create($validated);
        $address->load(['provincia', 'municipio', 'sector']);

        return response()->json([
            'success' => true,
            'message' => 'Direccion guardada exitosamente',
            'address' => $address,
        ]);
    }

    /**
     * Actualiza una direccion existente
     */
    public function updateAddress(Request $request, int $addressId): JsonResponse
    {
        $validated = $request->validate([
            'label' => 'required|string|max:50',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'provincia_id' => 'required|integer|exists:geo_provincias,id',
            'municipio_id' => 'required|integer|exists:geo_municipios,id',
            'sector_id' => 'nullable|integer|exists:geo_sectores,id',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'delivery_instructions' => 'nullable|string|max:500',
            'is_default' => 'nullable|boolean',
        ]);

        $user = Auth::user();
        $address = $user->addresses()->findOrFail($addressId);

        // Si es default, quitar default de otras direcciones
        if (!empty($validated['is_default'])) {
            $user->addresses()->where('id', '!=', $addressId)->update(['is_default' => false]);
        }

        $address->update($validated);
        $address->load(['provincia', 'municipio', 'sector']);

        return response()->json([
            'success' => true,
            'message' => 'Direccion actualizada exitosamente',
            'address' => $address,
        ]);
    }

    /**
     * Elimina una direccion
     */
    public function deleteAddress(Request $request, int $addressId): JsonResponse
    {
        $user = Auth::user();
        $address = $user->addresses()->findOrFail($addressId);

        $address->delete();

        return response()->json([
            'success' => true,
            'message' => 'Direccion eliminada exitosamente',
        ]);
    }

    /**
     * Muestra los favoritos del usuario
     */
    public function favorites(Request $request): Response|JsonResponse
    {
        $validated = $request->validate([
            'type' => 'nullable|string|in:restaurant,dish',
        ]);

        $user = Auth::user();
        $type = $validated['type'] ?? null;

        $favoritesQuery = $user->favorites()
            ->with(['favorable']);

        if ($type) {
            $favoritesQuery->where('favorable_type', match($type) {
                'restaurant' => 'App\\Models\\Restaurant',
                'dish' => 'App\\Models\\Dish',
            });
        }

        $favorites = $favoritesQuery->latest()->get();

        // Agrupar por tipo
        $groupedFavorites = [
            'restaurants' => $favorites->filter(fn($f) => $f->favorable_type === 'App\\Models\\Restaurant')
                ->map(fn($f) => $f->favorable),
            'dishes' => $favorites->filter(fn($f) => $f->favorable_type === 'App\\Models\\Dish')
                ->map(fn($f) => $f->favorable),
        ];

        if ($request->wantsJson()) {
            return response()->json($groupedFavorites);
        }

        return Inertia::render('Profile/Favorites', [
            'favorites' => $groupedFavorites,
            'filter' => $type,
        ]);
    }

    /**
     * Agrega o elimina un favorito
     */
    public function toggleFavorite(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:restaurant,dish',
            'id' => 'required|integer',
        ]);

        $user = Auth::user();

        $favorableType = match($validated['type']) {
            'restaurant' => 'App\\Models\\Restaurant',
            'dish' => 'App\\Models\\Dish',
        };

        $existing = $user->favorites()
            ->where('favorable_type', $favorableType)
            ->where('favorable_id', $validated['id'])
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json([
                'success' => true,
                'message' => 'Eliminado de favoritos',
                'is_favorite' => false,
            ]);
        }

        $user->favorites()->create([
            'favorable_type' => $favorableType,
            'favorable_id' => $validated['id'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Agregado a favoritos',
            'is_favorite' => true,
        ]);
    }

    /**
     * Muestra el wallet del usuario
     */
    public function wallet(Request $request): Response|JsonResponse
    {
        $validated = $request->validate([
            'type' => 'nullable|string|in:credit,debit',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $user = Auth::user();

        $transactionsQuery = $user->walletTransactions()
            ->with('order:id,order_number');

        if (!empty($validated['type'])) {
            $transactionsQuery->where('type', $validated['type']);
        }

        if (!empty($validated['from_date'])) {
            $transactionsQuery->whereDate('created_at', '>=', $validated['from_date']);
        }

        if (!empty($validated['to_date'])) {
            $transactionsQuery->whereDate('created_at', '<=', $validated['to_date']);
        }

        $transactions = $transactionsQuery
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $walletData = [
            'balance' => $user->wallet_balance,
            'formatted_balance' => 'RD$ ' . number_format($user->wallet_balance, 2),
            'transactions' => $transactions,
            'filters' => $validated,
        ];

        if ($request->wantsJson()) {
            return response()->json($walletData);
        }

        return Inertia::render('Profile/Wallet', $walletData);
    }

    /**
     * Muestra los puntos de lealtad del usuario
     */
    public function loyaltyPoints(Request $request): Response|JsonResponse
    {
        $validated = $request->validate([
            'type' => 'nullable|string|in:earned,redeemed',
        ]);

        $user = Auth::user();

        $transactionsQuery = $user->loyaltyTransactions()
            ->with('order:id,order_number');

        if (!empty($validated['type'])) {
            $transactionsQuery->where('type', $validated['type']);
        }

        $transactions = $transactionsQuery
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // Obtener tier actual y proximo
        $currentTier = $user->getCurrentLoyaltyTier();
        $nextTier = \App\Models\LoyaltyTier::where('points_required', '>', $user->loyalty_points)
            ->orderBy('points_required')
            ->first();

        $loyaltyData = [
            'points' => $user->loyalty_points,
            'current_tier' => $currentTier,
            'next_tier' => $nextTier,
            'points_to_next_tier' => $nextTier ? $nextTier->points_required - $user->loyalty_points : 0,
            'transactions' => $transactions,
            'filters' => $validated,
        ];

        if ($request->wantsJson()) {
            return response()->json($loyaltyData);
        }

        return Inertia::render('Profile/LoyaltyPoints', $loyaltyData);
    }
}
