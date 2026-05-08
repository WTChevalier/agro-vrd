<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Models\Dish;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Muestra la pagina principal con restaurantes destacados
     */
    public function index(Request $request): Response
    {
        $userLocation = $this->getUserLocation($request);

        // Restaurantes destacados
        $featuredRestaurants = Restaurant::query()
            ->active()
            ->featured()
            ->with(['provincia', 'municipio'])
            ->limit(8)
            ->get();

        // Restaurantes cercanos si hay ubicacion
        $nearbyRestaurants = collect();
        if ($userLocation['lat'] && $userLocation['lng']) {
            $nearbyRestaurants = Restaurant::query()
                ->active()
                ->open()
                ->acceptsDelivery()
                ->nearby($userLocation['lat'], $userLocation['lng'], 15)
                ->with(['provincia', 'municipio'])
                ->limit(12)
                ->get();
        }

        // Restaurantes populares por cantidad de ordenes
        $popularRestaurants = Restaurant::query()
            ->active()
            ->orderByDesc('total_orders')
            ->with(['provincia', 'municipio'])
            ->limit(8)
            ->get();

        // Platos destacados
        $featuredDishes = Dish::query()
            ->active()
            ->available()
            ->featured()
            ->with(['restaurant', 'category'])
            ->limit(12)
            ->get();

        // Tipos de cocina unicos
        $cuisineTypes = Restaurant::query()
            ->active()
            ->whereNotNull('cuisine_types')
            ->get()
            ->pluck('cuisine_types')
            ->flatten()
            ->unique()
            ->values()
            ->take(10);

        return Inertia::render('Home/Index', [
            'featuredRestaurants' => $featuredRestaurants,
            'nearbyRestaurants' => $nearbyRestaurants,
            'popularRestaurants' => $popularRestaurants,
            'featuredDishes' => $featuredDishes,
            'cuisineTypes' => $cuisineTypes,
            'userLocation' => $userLocation,
        ]);
    }

    /**
     * Busqueda de restaurantes y platos
     */
    public function search(Request $request): Response
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
            'cuisine' => 'nullable|string|max:100',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'rating' => 'nullable|numeric|min:0|max:5',
            'delivery' => 'nullable|boolean',
            'pickup' => 'nullable|boolean',
            'open_now' => 'nullable|boolean',
            'sort' => 'nullable|string|in:relevance,rating,distance,delivery_time,price_low,price_high',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
        ]);

        $query = $validated['q'] ?? '';
        $userLocation = $this->getUserLocation($request);

        // Buscar restaurantes
        $restaurantsQuery = Restaurant::query()
            ->active()
            ->when($query, function ($q) use ($query) {
                $q->where(function ($subQ) use ($query) {
                    $subQ->where('name', 'like', "%{$query}%")
                        ->orWhere('description', 'like', "%{$query}%")
                        ->orWhereJsonContains('cuisine_types', $query);
                });
            })
            ->when($validated['cuisine'] ?? null, function ($q, $cuisine) {
                $q->whereJsonContains('cuisine_types', $cuisine);
            })
            ->when($validated['rating'] ?? null, function ($q, $rating) {
                $q->where('rating', '>=', $rating);
            })
            ->when($validated['delivery'] ?? false, function ($q) {
                $q->acceptsDelivery();
            })
            ->when($validated['pickup'] ?? false, function ($q) {
                $q->where('accepts_pickup', true);
            })
            ->when($validated['open_now'] ?? false, function ($q) {
                $q->open();
            });

        // Ordenar resultados
        $sort = $validated['sort'] ?? 'relevance';
        match ($sort) {
            'rating' => $restaurantsQuery->orderByDesc('rating'),
            'distance' => $userLocation['lat'] && $userLocation['lng']
                ? $restaurantsQuery->nearby($userLocation['lat'], $userLocation['lng'], 50)
                : $restaurantsQuery->orderByDesc('total_orders'),
            'delivery_time' => $restaurantsQuery->orderBy('preparation_time'),
            'price_low' => $restaurantsQuery->orderBy('minimum_order'),
            'price_high' => $restaurantsQuery->orderByDesc('minimum_order'),
            default => $restaurantsQuery->orderByDesc('is_featured')->orderByDesc('rating'),
        };

        $restaurants = $restaurantsQuery
            ->with(['provincia', 'municipio'])
            ->paginate(20)
            ->withQueryString();

        // Buscar platos si hay termino de busqueda
        $dishes = collect();
        if ($query) {
            $dishesQuery = Dish::query()
                ->active()
                ->available()
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('description', 'like', "%{$query}%");
                })
                ->when($validated['min_price'] ?? null, function ($q, $minPrice) {
                    $q->where('price', '>=', $minPrice);
                })
                ->when($validated['max_price'] ?? null, function ($q, $maxPrice) {
                    $q->where('price', '<=', $maxPrice);
                })
                ->with(['restaurant', 'category'])
                ->limit(20);

            $dishes = $dishesQuery->get();
        }

        return Inertia::render('Home/Search', [
            'restaurants' => $restaurants,
            'dishes' => $dishes,
            'filters' => $validated,
            'query' => $query,
            'userLocation' => $userLocation,
        ]);
    }

    /**
     * Obtiene la ubicacion del usuario desde la request o sesion
     */
    private function getUserLocation(Request $request): array
    {
        $lat = $request->input('lat') ?? session('user_latitude');
        $lng = $request->input('lng') ?? session('user_longitude');

        if ($lat && $lng) {
            session(['user_latitude' => $lat, 'user_longitude' => $lng]);
        }

        return [
            'lat' => $lat ? (float) $lat : null,
            'lng' => $lng ? (float) $lng : null,
        ];
    }
}
