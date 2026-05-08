<?php

namespace App\Providers;

use App\Events\Order\OrderCancelled;
use App\Events\Order\OrderCompleted;
use App\Events\Order\OrderCreated;
use App\Events\Order\OrderDelivered;
use App\Events\Order\OrderPaid;
use App\Events\Order\OrderPreparing;
use App\Events\Order\OrderReadyForPickup;
use App\Events\Order\OrderStatusChanged;
use App\Listeners\Order\NotifyCustomerOrderCancelled;
use App\Listeners\Order\NotifyCustomerOrderCompleted;
use App\Listeners\Order\NotifyCustomerOrderCreated;
use App\Listeners\Order\NotifyCustomerOrderDelivered;
use App\Listeners\Order\NotifyCustomerOrderPreparing;
use App\Listeners\Order\NotifyCustomerOrderReady;
use App\Listeners\Order\NotifyRestaurantNewOrder;
use App\Listeners\Order\ProcessOrderPayment;
use App\Listeners\Order\SendOrderConfirmationEmail;
use App\Listeners\Order\UpdateOrderMetrics;
use App\Listeners\Order\UpdateRestaurantStats;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        /*
        |--------------------------------------------------------------------------
        | Order Events
        |--------------------------------------------------------------------------
        |
        | Events related to order lifecycle management. These events are fired
        | at various stages of an order's lifecycle and trigger appropriate
        | notifications and processing.
        |
        */

        OrderCreated::class => [
            SendOrderConfirmationEmail::class,
            NotifyRestaurantNewOrder::class,
            NotifyCustomerOrderCreated::class,
            UpdateOrderMetrics::class,
        ],

        OrderPaid::class => [
            ProcessOrderPayment::class,
            UpdateRestaurantStats::class,
        ],

        OrderStatusChanged::class => [
            UpdateOrderMetrics::class,
        ],

        OrderPreparing::class => [
            NotifyCustomerOrderPreparing::class,
        ],

        OrderReadyForPickup::class => [
            NotifyCustomerOrderReady::class,
        ],

        OrderDelivered::class => [
            NotifyCustomerOrderDelivered::class,
            UpdateRestaurantStats::class,
        ],

        OrderCompleted::class => [
            NotifyCustomerOrderCompleted::class,
            UpdateRestaurantStats::class,
            UpdateOrderMetrics::class,
        ],

        OrderCancelled::class => [
            NotifyCustomerOrderCancelled::class,
            UpdateOrderMetrics::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
