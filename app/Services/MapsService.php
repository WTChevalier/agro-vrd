<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class MapsService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.google_maps.api_key');
    }

    /**
     * Calcular distancia entre dos puntos
     */
    public function calcularDistancia($origen, $destino)
    {
        $cacheKey = "distance_{$origen['lat']}_{$origen['lng']}_{$destino['lat']}_{$destino['lng']}";

        return Cache::remember($cacheKey, 3600, function () use ($origen, $destino) {
            $response = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json', [
                'origins' => "{$origen['lat']},{$origen['lng']}",
                'destinations' => "{$destino['lat']},{$destino['lng']}",
                'key' => $this->apiKey,
                'units' => 'metric',
            ]);

            $data = $response->json();

            if ($data['status'] === 'OK' && isset($data['rows'][0]['elements'][0])) {
                $element = $data['rows'][0]['elements'][0];
                return [
                    'distancia' => $element['distance']['value'] / 1000, // km
                    'duracion' => $element['duration']['value'] / 60, // minutos
                    'distancia_texto' => $element['distance']['text'],
                    'duracion_texto' => $element['duration']['text'],
                ];
            }

            return null;
        });
    }

    /**
     * Geocodificar dirección
     */
    public function geocodificar($direccion)
    {
        $cacheKey = 'geocode_' . md5($direccion);

        return Cache::remember($cacheKey, 86400, function () use ($direccion) {
            $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $direccion . ', República Dominicana',
                'key' => $this->apiKey,
            ]);

            $data = $response->json();

            if ($data['status'] === 'OK' && isset($data['results'][0])) {
                $location = $data['results'][0]['geometry']['location'];
                return [
                    'lat' => $location['lat'],
                    'lng' => $location['lng'],
                    'direccion_formateada' => $data['results'][0]['formatted_address'],
                ];
            }

            return null;
        });
    }

    /**
     * Geocodificación inversa
     */
    public function geocodificacionInversa($lat, $lng)
    {
        $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
            'latlng' => "{$lat},{$lng}",
            'key' => $this->apiKey,
        ]);

        $data = $response->json();

        if ($data['status'] === 'OK' && isset($data['results'][0])) {
            return $data['results'][0]['formatted_address'];
        }

        return null;
    }

    /**
     * Obtener ruta entre dos puntos
     */
    public function obtenerRuta($origen, $destino)
    {
        $response = Http::get('https://maps.googleapis.com/maps/api/directions/json', [
            'origin' => "{$origen['lat']},{$origen['lng']}",
            'destination' => "{$destino['lat']},{$destino['lng']}",
            'key' => $this->apiKey,
            'mode' => 'driving',
        ]);

        $data = $response->json();

        if ($data['status'] === 'OK' && isset($data['routes'][0])) {
            $route = $data['routes'][0];
            return [
                'polyline' => $route['overview_polyline']['points'],
                'distancia' => $route['legs'][0]['distance']['text'],
                'duracion' => $route['legs'][0]['duration']['text'],
                'pasos' => collect($route['legs'][0]['steps'])->map(function ($step) {
                    return [
                        'instruccion' => strip_tags($step['html_instructions']),
                        'distancia' => $step['distance']['text'],
                    ];
                }),
            ];
        }

        return null;
    }

    /**
     * Calcular costo de delivery basado en distancia
     */
    public function calcularCostoDelivery($restaurante, $direccionCliente)
    {
        $origen = [
            'lat' => $restaurante->latitud,
            'lng' => $restaurante->longitud,
        ];

        $destino = $this->geocodificar($direccionCliente);

        if (!$destino) {
            return $restaurante->costo_delivery; // Costo base
        }

        $resultado = $this->calcularDistancia($origen, $destino);

        if (!$resultado) {
            return $restaurante->costo_delivery;
        }

        $distanciaKm = $resultado['distancia'];

        // Tarifa: base + (distancia * precio por km)
        $tarifaBase = 50; // RD$
        $precioPorKm = 15; // RD$

        $costo = $tarifaBase + ($distanciaKm * $precioPorKm);

        // Mínimo y máximo
        $costo = max($restaurante->costo_delivery, $costo);
        $costo = min(300, $costo); // Máximo RD$300

        return round($costo);
    }
}