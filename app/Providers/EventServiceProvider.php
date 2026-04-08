<?php

namespace App\Providers;

use App\Events\OrderCreated;
use App\Events\OrderConfirmed;
use App\Events\DeliveryDispatched;
use App\Events\DeliveryCompleted;
use App\Events\DriverVerified;
use App\Listeners\SendOrderCreatedNotifications;
use App\Listeners\SendDeliveryDispatchedNotifications;
use App\Listeners\SendDeliveryCompletedNotifications;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        OrderCreated::class => [
            SendOrderCreatedNotifications::class,
        ],
        
        DeliveryDispatched::class => [
            SendDeliveryDispatchedNotifications::class,
        ],
        
        DeliveryCompleted::class => [
            SendDeliveryCompletedNotifications::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }
}
