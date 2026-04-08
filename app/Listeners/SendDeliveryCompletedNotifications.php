<?php

namespace App\Listeners;

use App\Events\DeliveryCompleted;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class SendDeliveryCompletedNotifications
{
    public function handle(DeliveryCompleted $event)
    {
        $delivery = $event->delivery;
        $order = $delivery->order;

        try {
            // Notify consumer
            $consumer = $order->consumer;
            if ($consumer) {
                Notification::create([
                    'user_id' => $consumer->id,
                    'title' => '✅ Your Order Has Been Delivered',
                    'message' => "Order #{$order->order_number} has been successfully delivered! Please rate your experience.",
                    'type' => 'order',
                    'channel' => 'in_app',
                    'data' => json_encode([
                        'order_id' => $order->id,
                        'delivery_id' => $delivery->id,
                        'can_rate' => true,
                    ]),
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            }

            // Notify farm owner
            $farmOwnerUser = $delivery->farmOwner->user;
            if ($farmOwnerUser) {
                $isCod = $order->payment_method === 'cod';
                $message = "Order #{$order->order_number} delivered to {$order->delivery_city}.";
                if ($isCod) {
                    $message .= " Mark payment as confirmed if COD.";
                }
                
                Notification::create([
                    'user_id' => $farmOwnerUser->id,
                    'title' => '✔️ Delivery Completed',
                    'message' => $message,
                    'type' => 'order',
                    'channel' => 'in_app',
                    'data' => json_encode([
                        'order_id' => $order->id,
                        'delivery_id' => $delivery->id,
                        'is_cod' => $isCod,
                    ]),
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            }

            // Notify driver
            $driver = $delivery->driver;
            if ($driver && $driver->user) {
                Notification::create([
                    'user_id' => $driver->user->id,
                    'title' => '💰 Delivery Completed',
                    'message' => "Delivery completed! You've earned ₱{$delivery->delivery_fee} for order #{$order->order_number}",
                    'type' => 'order',
                    'channel' => 'in_app',
                    'data' => json_encode([
                        'delivery_id' => $delivery->id,
                        'delivery_fee' => $delivery->delivery_fee,
                    ]),
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            }

            Log::info('Delivery completed notifications sent', [
                'delivery_id' => $delivery->id,
                'order_id' => $order->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send delivery completed notifications', [
                'delivery_id' => $delivery->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
