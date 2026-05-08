<?php

namespace Database\Seeders;

use App\Models\OrderStatus;
use Illuminate\Database\Seeder;

class OrderStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            [
                'name' => 'Pendiente',
                'slug' => 'pending',
                'description' => 'El pedido ha sido recibido y está esperando confirmación del restaurante',
                'color' => 'warning',
                'icon' => 'heroicon-o-clock',
                'sort_order' => 1,
                'notify_customer' => true,
                'notify_restaurant' => true,
            ],
            [
                'name' => 'Confirmado',
                'slug' => 'confirmed',
                'description' => 'El restaurante ha confirmado el pedido',
                'color' => 'info',
                'icon' => 'heroicon-o-check-circle',
                'sort_order' => 2,
                'notify_customer' => true,
                'notify_restaurant' => false,
            ],
            [
                'name' => 'En Preparación',
                'slug' => 'preparing',
                'description' => 'El restaurante está preparando el pedido',
                'color' => 'primary',
                'icon' => 'heroicon-o-fire',
                'sort_order' => 3,
                'notify_customer' => true,
                'notify_restaurant' => false,
            ],
            [
                'name' => 'Listo para Recoger',
                'slug' => 'ready',
                'description' => 'El pedido está listo para ser recogido por el repartidor o cliente',
                'color' => 'success',
                'icon' => 'heroicon-o-shopping-bag',
                'sort_order' => 4,
                'notify_customer' => true,
                'notify_restaurant' => false,
            ],
            [
                'name' => 'En Camino',
                'slug' => 'on_the_way',
                'description' => 'El repartidor está en camino con el pedido',
                'color' => 'info',
                'icon' => 'heroicon-o-truck',
                'sort_order' => 5,
                'notify_customer' => true,
                'notify_restaurant' => true,
            ],
            [
                'name' => 'Entregado',
                'slug' => 'delivered',
                'description' => 'El pedido ha sido entregado exitosamente',
                'color' => 'success',
                'icon' => 'heroicon-o-check-badge',
                'sort_order' => 6,
                'notify_customer' => true,
                'notify_restaurant' => true,
            ],
            [
                'name' => 'Cancelado',
                'slug' => 'cancelled',
                'description' => 'El pedido ha sido cancelado',
                'color' => 'danger',
                'icon' => 'heroicon-o-x-circle',
                'sort_order' => 7,
                'notify_customer' => true,
                'notify_restaurant' => true,
            ],
            [
                'name' => 'Reembolsado',
                'slug' => 'refunded',
                'description' => 'El pedido ha sido reembolsado',
                'color' => 'gray',
                'icon' => 'heroicon-o-arrow-uturn-left',
                'sort_order' => 8,
                'notify_customer' => true,
                'notify_restaurant' => true,
            ],
        ];

        foreach ($statuses as $status) {
            OrderStatus::updateOrCreate(
                ['slug' => $status['slug']],
                $status
            );
        }
    }
}
