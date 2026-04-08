<?php

namespace App\Listeners;

use App\Events\DeliveryDispatched;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class SendDeliveryDispatchedNotifications
{
    public function handle(DeliveryDispatched $event)
    {
        $delivery = $event->delivery;
        $order = $delivery->order;

        try {
            // Notify consumer
            $consumer = $order->consumer;
            if ($consumer) {
                $driverName = $delivery->driver?->name ?? 'Assigned Driver';
                
                Notification::create([
                    'user_id' => $consumer->id,
                    'title' => '🚚 Your Order is Out for Delivery',
                    'message' => "Your order #{$order->order_number} is on the way! Driver: {$driverName}",
                    'type' => 'order',
                    'channel' => 'in_app',
                    'data' => json_encode([
                        'order_id' => $order->id,
                        'delivery_id' => $delivery->id,
                        'order_number' => $order->order_number,
                        'driver_id' => $delivery->driver_id,
                        'driver_name' => $driverName,
                    ]),
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            }

            // Notify farm owner (logistics)
            $farmOwnerUser = $delivery->farmOwner->user;
            if ($farmOwnerUser) {
                $city = $order->delivery_city ?? 'destination';
                $driverName = $delivery->driver?->name ?? 'Driver';
                
                Notification::create([
                    'user_id' => $farmOwnerUser->id,
                    'title' => '🚗 Delivery Dispatched',
                    'message' => "Order #{$order->order_number} dispatched to {$driverName} heading to {$city}",
                    'type' => 'order',
                    'channel' => 'in_app',
                    'data' => json_encode([
                        'order_id' => $order->id,
                        'delivery_id' => $delivery->id,
                        'driver_id' => $delivery->driver_id,
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
                    'title' => '📍 New Delivery Assigned',
                    'message' => "Delivery of order #{$order->order_number} assigned. Heading to {$order->delivery_city}. Delivery fee: ₱{$delivery->delivery_fee}",
                    'type' => 'order',
                    'channel' => 'in_app',
                    'data' => json_encode([
                        'order_id' => $order->id,
                        'delivery_id' => $delivery->id,
                        'delivery_fee' => $delivery->delivery_fee,
                    ]),
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            }

            Log::info('Delivery dispatched notifications sent', [
                'delivery_id' => $delivery->id,
                'order_id' => $order->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send delivery dispatched notifications', [
                'delivery_id' => $delivery->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
