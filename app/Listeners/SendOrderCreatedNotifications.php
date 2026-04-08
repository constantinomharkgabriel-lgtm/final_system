<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class SendOrderCreatedNotifications
{
    public function handle(OrderCreated $event)
    {
        $order = $event->order;

        try {
            // Notify farm owner
            $farmOwnerUser = $order->farmOwner->user;
            if ($farmOwnerUser) {
                Notification::create([
                    'user_id' => $farmOwnerUser->id,
                    'title' => '📦 New Order Received',
                    'message' => "New order #{$order->order_number} from {$order->consumer->name} for ₱{$order->total_amount}",
                    'type' => 'order',
                    'channel' => 'in_app',
                    'data' => json_encode([
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'amount' => $order->total_amount,
                        'consumer_name' => $order->consumer->name,
                    ]),
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            }

            // In future: Send email, SMS, push notifications
            Log::info('Order created notifications sent', ['order_id' => $order->id]);
        } catch (\Exception $e) {
            Log::error('Failed to send order created notifications', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
