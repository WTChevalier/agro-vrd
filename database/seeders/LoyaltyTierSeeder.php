<?php

namespace Database\Seeders;

use App\Models\LoyaltyTier;
use Illuminate\Database\Seeder;

class LoyaltyTierSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            [
                'name' => 'Bronce',
                'slug' => 'bronze',
                'points_required' => 0,
                'points_multiplier' => 1.0,
                'discount_percentage' => 0,
                'free_delivery' => false,
                'perks' => [
                    'Acumula 1 punto por cada RD$100',
                    'Acceso a promociones exclusivas',
                ],
                'badge_color' => '#CD7F32',
                'badge_icon' => 'heroicon-o-trophy',
            ],
            [
                'name' => 'Plata',
                'slug' => 'silver',
                'points_required' => 500,
                'points_multiplier' => 1.25,
                'discount_percentage' => 5,
                'free_delivery' => false,
                'perks' => [
                    'Acumula 1.25 puntos por cada RD$100',
                    '5% descuento en todos los pedidos',
                    'Prioridad en soporte',
                ],
                'badge_color' => '#C0C0C0',
                'badge_icon' => 'heroicon-o-trophy',
            ],
            [
                'name' => 'Oro',
                'slug' => 'gold',
                'points_required' => 2000,
                'points_multiplier' => 1.5,
                'discount_percentage' => 10,
                'free_delivery' => false,
                'perks' => [
                    'Acumula 1.5 puntos por cada RD$100',
                    '10% descuento en todos los pedidos',
                    'Delivery gratis en pedidos mayores a RD$800',
                    'Acceso anticipado a nuevos restaurantes',
                ],
                'badge_color' => '#FFD700',
                'badge_icon' => 'heroicon-s-trophy',
            ],
            [
                'name' => 'Platino',
                'slug' => 'platinum',
                'points_required' => 5000,
                'points_multiplier' => 2.0,
                'discount_percentage' => 15,
                'free_delivery' => true,
                'perks' => [
                    'Acumula 2 puntos por cada RD$100',
                    '15% descuento en todos los pedidos',
                    'Delivery GRATIS en todos los pedidos',
                    'Soporte prioritario 24/7',
                    'Invitaciones a eventos exclusivos',
                    'Regalos sorpresa mensuales',
                ],
                'badge_color' => '#E5E4E2',
                'badge_icon' => 'heroicon-s-star',
            ],
        ];

        foreach ($tiers as $tier) {
            LoyaltyTier::updateOrCreate(
                ['slug' => $tier['slug']],
                $tier
            );
        }
    }
}
