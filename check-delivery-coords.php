<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$delivery = \App\Models\Delivery::where('tracking_number', 'TRK-69D61305C42FD')->first();
if ($delivery) {
    echo "Delivery: TRK-69D61305C42FD\n";
    echo "Latitude: " . ($delivery->latitude ?? 'NULL') . "\n";
    echo "Longitude: " . ($delivery->longitude ?? 'NULL') . "\n";
    echo "Address: " . $delivery->delivery_address . "\n";
} else {
    echo "Delivery not found\n";
}
