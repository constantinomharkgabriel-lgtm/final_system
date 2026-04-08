<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;
use App\Models\PayMongoWebhookEvent;
use Illuminate\Support\Facades\Log;

echo "\n=== PAYMENT DIAGNOSIS ===\n\n";

// Find the order from the screenshot
$order = Order::where('order_number', 'ORD-69D5F2147541D3')->first();

if (!$order) {
    // Try partial match
    $order = Order::whereRaw("CAST(order_number AS TEXT) LIKE ?", ['%69D5F2147541D%'])->first();
}

if (!$order) {
    // Get the latest order
    $order = Order::latest()->first();
    echo "No order with that number found. Latest order:\n";
}

if ($order) {
    echo "ORDER DETAILS:\n";
    echo "├─ ID: {$order->id}\n";
    echo "├─ Order #: {$order->order_number}\n";
    echo "├─ Payment Status: {$order->payment_status}\n";
    echo "├─ Payment Method: {$order->payment_method}\n";
    echo "├─ PayMongo ID: {$order->paymongo_payment_id}\n";
    echo "├─ Total: PHP {$order->total_amount}\n";
    $consumerName = $order->consumer ? $order->consumer->name : 'N/A';
    $farmName = $order->farmOwner ? $order->farmOwner->farm_name : 'N/A';
    echo "├─ Consumer: {$consumerName}\n";
    echo "├─ Farm Owner: {$farmName}\n";
    echo "└─ Created: {$order->created_at}\n\n";

    // Check for webhook events related to this order
    echo "WEBHOOK EVENTS:\n";
    $webhookEvents = PayMongoWebhookEvent::where('payload', 'LIKE', '%' . (string)$order->id . '%')
        ->orderBy('created_at', 'desc')
        ->get(['id', 'event_id', 'event_type', 'status', 'response_code', 'created_at', 'processed_at']);
    
    if ($webhookEvents->isEmpty()) {
        echo "❌ NO WEBHOOK EVENTS FOUND FOR THIS ORDER\n\n";
    } else {
        foreach ($webhookEvents as $event) {
            echo "├─ Event: {$event->event_type}\n";
            echo "│  ├─ ID: {$event->event_id}\n";
            echo "│  ├─ Status: {$event->status}\n";
            echo "│  ├─ Response Code: {$event->response_code}\n";
            echo "│  ├─ Created: {$event->created_at}\n";
            echo "│  └─ Processed: {$event->processed_at}\n";
        }
        echo "\n";
    }

    // Check PayMongo Payment Integration
    echo "PAYMENT SERVICE CHECK:\n";
    if (config('services.paymongo.secret_key')) {
        echo "✓ PayMongo Secret Key: Configured\n";
    } else {
        echo "❌ PayMongo Secret Key: NOT configured!\n";
    }

    if (config('services.paymongo.webhook_secret')) {
        echo "✓ PayMongo Webhook Secret: Configured\n";
    } else {
        echo "⚠️  PayMongo Webhook Secret: NOT configured (optional)\n";
    }

    echo "\n";

    // Check if payment verification endpoint would work
    echo "PAYMENT VERIFICATION STATUS:\n";
    if ($order->paymongo_payment_id) {
        echo "✓ Order has PayMongo Payment ID: {$order->paymongo_payment_id}\n";
        
        // Check if it's a checkout session or payment link
        if (str_starts_with($order->paymongo_payment_id, 'cs_')) {
            echo "  └─ Type: Checkout Session\n";
        } elseif (str_starts_with($order->paymongo_payment_id, 'link_')) {
            echo "  └─ Type: Payment Link\n";
        } else {
            echo "  └─ Type: Unknown\n";
        }
    } else {
        echo "❌ Order has NO PayMongo Payment ID!\n";
    }

    echo "\n";

    // Check cache
    echo "CACHE CHECK:\n";
    $cacheKey = "web_checkout_session_{$order->id}";
    if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
        echo "✓ Cache key exists: {$cacheKey}\n";
        echo "  └─ Value: " . \Illuminate\Support\Facades\Cache::get($cacheKey) . "\n";
    } else {
        echo "❌ Cache key missing: {$cacheKey}\n";
    }

    echo "\n";

    // Recommendations
    echo "RECOMMENDATIONS:\n";
    if ($order->payment_status === 'unpaid') {
        echo "1. ⚠️  Order payment status is UNPAID\n";
        
        if (!$order->paymongo_payment_id) {
            echo "   → Payment was never initiated!\n";
            echo "   → Consumer needs to click 'Retry Payment' to start checkout\n";
        } elseif ($webhookEvents->isEmpty()) {
            echo "   → Payment might have succeeded but webhook didn't fire\n";
            echo "   → TRY: Visit /orders/{$order->id}/verify-payment to manually verify\n";
            echo "   → OR: Check PayMongo dashboard if webhook is registered\n";
        } else {
            echo "   → Webhook fired but didn't update order\n";
            echo "   → Check logs: storage/logs/laravel.log\n";
        }
    }

} else {
    echo "❌ No orders found in database!\n";
}

echo "\n";
?>
