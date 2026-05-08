<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Models\MenuCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RestaurantController extends Controller
{
    /**
     * Lista todos los restaurantes activos con filtros
     */
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'cuisine' => 'nullable|string|max:100',
            'provincia_id' => 'nullable|integer|exists:geo_provincias,id',
            'municipio_id' => 'nullable|integer|exists:geo_municipios,id',
            'rating' => 'nullable|numeric|min:0|max:5',
            'delivery' => 'nullable|boolean',
            'pickup' => 'nullable|boolean',
            'open_now' => 'nullable|boolean',
            'featured' => 'nullable|boolean',
            'sort' => 'nullable|string|in:name,rating,total_orders,delivery_fee',
            'order' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:10|max:50',
        ]);

        $query = Restaurant::query()
            ->active()
            ->with(['provincia', 'municipio', 'sector']);

        // Aplicar filtros
        if (!empty($validated['cuisine'])) {
            $query->whereJsonContains('cuisine_types', $validated['cuisine']);
        }

        if (!empty($validated['provincia_id'])) {
            $query->where('provincia_id', $validated['provincia_id']);
        }

        if (!empty($validated['municipio_id'])) {
            $query->where('municipio_id', $validated['municipio_id']);
        }

        if (!empty($validated['rating'])) {
            $query->where('rating', '>=', $validated['rating']);
        }

        if (!empty($validated['delivery'])) {
            $query->acceptsDelivery();
        }

        if (!empty($validated['pickup'])) {
            $query->where('accepts_pickup', true);
        }

        if (!empty($validated['open_now'])) {
            $query->open();
        }

        if (!empty($validated['featured'])) {
            $query->featured();
        }

        // Ordenamiento
        $sortField = $validated['sort'] ?? 'total_orders';
        $sortOrder = $validated['order'] ?? 'desc';
        $query->orderBy($sortField, $sortOrder);

        $perPage = $validated['per_page'] ?? 20;
        $restaurants = $query->paginate($perPage)->withQueryString();

        // Obtener tipos de cocina para filtros
        $cuisineTypes = Restaurant::query()
            ->active()
            ->whereNotNull('cuisine_types')
            ->get()
            ->pluck('cuisine_types')
            ->flatten()
            ->unique()
            ->sort()
            ->values();

        return Inertia::render('Restaurant/Index', [
            'restaurants' => $restaurants,
            'cuisineTypes' => $cuisineTypes,
            'filters' => $validated,
        ]);
    }

    /**
     * Muestra los detalles de un restaurante
     */
    public function show(Request $request, string $slug): Response
    {
        $restaurant = Restaurant::query()
            ->where('slug', $slug)
            ->active()
            ->with([
                'provincia',
                'municipio',
                'sector',
                'menuCategories' => function ($query) {
                    $query->where('is_active', true)
                        ->orderBy('sort_order');
                },
                'menuCategories.dishes' => function ($query) {
                    $query->active()
                        ->available()
                        ->orderBy('sort_order');
                },
                'reviews' => function ($query) {
                    $query->where('status', 'approved')
                        ->with('user:id,name,avatar')
                        ->latest()
                        ->limit(10);
                },
            ])
            ->firstOrFail();

        // Platos populares del restaurante
        $popularDishes = $restaurant->dishes()
            ->active()
            ->available()
            ->popular()
            ->with('category')
            ->limit(6)
            ->get();

        // Cupones publicos del restaurante
        $coupons = $restaurant->coupons()
            ->valid()
            ->public()
            ->get(['code', 'name', 'description', 'type', 'value', 'min_order_amount']);

        // Verificar si esta abierto actualmente
        $isCurrentlyOpen = $restaurant->isCurrentlyOpen();

        // Restaurantes similares
        $similarRestaurants = Restaurant::query()
            ->active()
            ->where('id', '!=', $restaurant->id)
            ->when($restaurant->cuisine_types, function ($query) use ($restaurant) {
                foreach ($restaurant->cuisine_types as $cuisine) {
                    $query->orWhereJsonContains('cuisine_types', $cuisine);
                }
            })
            ->limit(4)
            ->get();

        return Inertia::render('Restaurant/Show', [
            'restaurant' => $restaurant,
            'popularDishes' => $popularDishes,
            'coupons' => $coupons,
            'isCurrentlyOpen' => $isCurrentlyOpen,
            'similarRestaurants' => $similarRestaurants,
        ]);
    }

    /**
     * Obtiene restaurantes cercanos a una ubicacion
     */
    public function nearby(Request $request): Response
    {
        $validated = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1|max:50',
            'delivery' => 'nullable|boolean',
            'open_now' => 'nullable|boolean',
        ]);

        $radius = $validated['radius'] ?? 10;

        $query = Restaurant::query()
            ->active()
            ->nearby($validated['lat'], $validated['lng'], $radius)
            ->with(['provincia', 'municipio']);

        if (!empty($validated['delivery'])) {
            $query->acceptsDelivery();
        }

        if (!empty($validated['open_now'])) {
            $query->open();
        }

        $restaurants = $query->paginate(20)->withQueryString();

        return Inertia::render('Restaurant/Nearby', [
            'restaurants' => $restaurants,
            'location' => [
                'lat' => $validated['lat'],
                'lng' => $validated['lng'],
            ],
            'radius' => $radius,
            'filters' => $validated,
        ]);
    }

    /**
     * Restaurantes filtrados por categoria de cocina
     */
    public function byCategory(Request $request, string $category): Response
    {
        $validated = $request->validate([
            'provincia_id' => 'nullable|integer|exists:geo_provincias,id',
            'rating' => 'nullable|numeric|min:0|max:5',
            'delivery' => 'nullable|boolean',
            'open_now' => 'nullable|boolean',
            'sort' => 'nullable|string|in:name,rating,total_orders',
        ]);

        $query = Restaurant::query()
            ->active()
            ->whereJsonContains('cuisine_types', $category)
            ->with(['provincia', 'municipio']);

        if (!empty($validated['provincia_id'])) {
            $query->where('provincia_id', $validated['provincia_id']);
        }

        if (!empty($validated['rating'])) {
            $query->where('rating', '>=', $validated['rating']);
        }

        if (!empty($validated['delivery'])) {
            $query->acceptsDelivery();
        }

        if (!empty($validated['open_now'])) {
            $query->open();
        }

        $sortField = $validated['sort'] ?? 'total_orders';
        $query->orderByDesc($sortField);

        $restaurants = $query->paginate(20)->withQueryString();

        // Obtener todas las categorias de cocina disponibles
        $allCuisineTypes = Restaurant::query()
            ->active()
            ->whereNotNull('cuisine_types')
            ->get()
            ->pluck('cuisine_types')
            ->flatten()
            ->unique()
            ->sort()
            ->values();

        return Inertia::render('Restaurant/ByCategory', [
            'restaurants' => $restaurants,
            'category' => $category,
            'allCuisineTypes' => $allCuisineTypes,
            'filters' => $validated,
        ]);
    }
}
