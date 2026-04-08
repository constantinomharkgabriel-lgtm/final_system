<?php
/**
 * PAYMENT SYSTEM FIX - Run this script to resolve payment issues
 */
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;
use App\Services\PayMongoService;
use Illuminate\Support\Facades\Log;

echo "\n=== PAYMENT VERIFICATION & FIX ===\n\n";

// Find unpaid orders with PayMongo IDs
$unpaidOrders = Order::where('payment_status', 'unpaid')
    ->whereNotNull('paymongo_payment_id')
    ->whereIn('payment_method', ['gcash', 'paymaya'])
    ->get();

if ($unpaidOrders->isEmpty()) {
    echo "✓ No unpaid orders to verify\n";
    return;
}

$paymongo = new PayMongoService();
$fixedCount = 0;

foreach ($unpaidOrders as $order) {
    echo "Checking Order: {$order->order_number}\n";
    echo "├─ PayMongo ID: {$order->paymongo_payment_id}\n";
    
    // Check if it's a checkout session or payment link
    if (str_starts_with($order->paymongo_payment_id, 'cs_')) {
        // Checkout session
        $sessionData = $paymongo->retrieveCheckoutSession($order->paymongo_payment_id);
        
        if ($sessionData) {
            $status = $sessionData['attributes']['payment_intent']['attributes']['status'] ?? null;
            $payments = $sessionData['attributes']['payments'] ?? [];
            
            echo "├─ PayMongo Status: {$status}\n";
            echo "├─ Payments Count: " . count($payments) . "\n";
            
            if ($status === 'succeeded' || !empty($payments)) {
                // Payment succeeded - update order
                echo "├─ ✓ PAYMENT VERIFIED IN PAYMONGO\n";
                
                $order->update([
                    'payment_status' => 'paid',
                ]);
                
                // Create notification
                \App\Models\Notification::create([
                    'user_id' => $order->consumer_id,
                    'title' => 'Payment Confirmed',
                    'message' => "Payment for order {$order->order_number} was confirmed via PayMongo.",
                    'type' => 'system',
                    'channel' => 'in_app',
                    'data' => json_encode([
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                    ]),
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
                
                Log::info("Order payment manually verified and updated", [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ]);
                
                echo "└─ ✓ ORDER UPDATED TO PAID\n\n";
                $fixedCount++;
            } else {
                echo "└─ ⚠️  Payment not yet confirmed in PayMongo\n\n";
            }
        } else {
            echo "└─ ❌ Could not retrieve session from PayMongo\n\n";
        }
        
    } elseif (str_starts_with($order->paymongo_payment_id, 'link_')) {
        // Payment link
        $paymentData = $paymongo->retrievePaymentLink($order->paymongo_payment_id);
        
        if ($paymentData) {
            $status = $paymentData['attributes']['status'] ?? null;
            
            echo "├─ PayMongo Status: {$status}\n";
            
            if ($status === 'paid') {
                echo "├─ ✓ PAYMENT VERIFIED IN PAYMONGO\n";
                
                $order->update([
                    'payment_status' => 'paid',
                ]);
                
                \App\Models\Notification::create([
                    'user_id' => $order->consumer_id,
                    'title' => 'Payment Confirmed',
                    'message' => "Payment for order {$order->order_number} was confirmed via PayMongo.",
                    'type' => 'system',
                    'channel' => 'in_app',
                    'data' => json_encode([
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                    ]),
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
                
                Log::info("Order payment manually verified and updated (link)", [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ]);
                
                echo "└─ ✓ ORDER UPDATED TO PAID\n\n";
                $fixedCount++;
            } else {
                echo "└─ ⚠️  Payment not yet confirmed in PayMongo\n\n";
            }
        } else {
            echo "└─ ❌ Could not retrieve payment link from PayMongo\n\n";
        }
    }
}

echo "\n=== SUMMARY ===\n";
echo "Total orders fixed: {$fixedCount} / {$unpaidOrders->count()}\n\n";

if ($fixedCount > 0) {
    echo "✓ Payment fix completed\n";
    echo "✓ Customers notified\n";
    echo "✓ Orders ready for logistics\n\n";
}
?>
