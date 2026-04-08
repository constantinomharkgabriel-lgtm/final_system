<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fires when an order is created
 * - Notify farm owner of new order
 * - Create pending notification
 */
class OrderCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Order $order)
    {
    }
}
