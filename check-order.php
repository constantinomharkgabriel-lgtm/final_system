<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;

$order = Order::latest()->first();
var_dump([
    'id' => $order->id,
    'number' => $order->order_number,
    'payment_status' => $order->payment_status,
    'payment_method' => $order->payment_method,
    'paymongo_id' => $order->paymongo_payment_id,
]);
?>
