<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Notification;
use App\Models\Order;

echo "\n=== NOTIFICATION VERIFICATION ===\n\n";

// Get the specific order
$order = Order::where('order_number', 'ORD-69D5FE7148CDC')->first();

if ($order) {
    echo "📦 Order: {$order->order_number}\n";
    echo "   Amount: ₱{$order->total_amount}\n";
    echo "   Consumer: {$order->consumer->name}\n";
    echo "   Farm Owner: {$order->farmOwner->farm_name}\n";
    echo "   Payment Status: {$order->payment_status}\n";
    echo "   Order Status: {$order->status}\n";
    $deliveryStatus = $order->delivery?->status ?? 'No delivery yet';
    echo "   Delivery Status: {$deliveryStatus}\n\n";

    // Get all notifications related to farm owner and consumer
    $farmOwnerUserId = $order->farmOwner->user->id;
    $consumerUserId = $order->consumer->id;

    $notifications = Notification::whereIn('user_id', [$farmOwnerUserId, $consumerUserId])
        ->where('data', 'like', '%' . $order->id . '%')
        ->orWhere('data', 'like', '%' . $order->order_number . '%')
        ->latest('created_at')
        ->get();

    if ($notifications->isEmpty()) {
        echo "⚠️  No notifications found yet.\n";
    } else {
        echo "✅ NOTIFICATIONS FOUND: " . $notifications->count() . "\n\n";
        foreach ($notifications as $notif) {
            $user = \App\Models\User::find($notif->user_id);
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            echo "📌 {$notif->title}\n";
            echo "   Message: {$notif->message}\n";
            echo "   For: {$user->name ?? 'N/A'} ({$user->role ?? 'N/A'})\n";
            echo "   Type: {$notif->type}\n";
            echo "   Status: {$notif->status}\n";
            echo "   Sent: {$notif->sent_at?->format('M d, Y H:i:s') ?? 'Not sent'}\n";
        }
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    }

    // Show delivery info if exists
    if ($order->delivery) {
        echo "\n🚗 DELIVERY DETAILS:\n";
        echo "   Tracking #: {$order->delivery->tracking_number}\n";
        echo "   Status: {$order->delivery->status}\n";
        echo "   Driver: {$order->delivery->driver?->name ?? 'Unassigned'}\n";
        echo "   Scheduled: {$order->delivery->scheduled_date?->format('M d, Y') ?? 'N/A'}\n";
    }
} else {
    echo "❌ Order not found!\n";
}

echo "\n";
