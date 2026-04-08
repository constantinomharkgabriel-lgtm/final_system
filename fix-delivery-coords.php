<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Update delivery with coordinates for Greengate, Kawit area
$delivery = \App\Models\Delivery::where('tracking_number', 'TRK-69D61305C42FD')->first();
if ($delivery) {
    // Greengate, Kawit area coordinates
    $delivery->latitude = 14.3633;
    $delivery->longitude = 120.8806;
    $delivery->save();
    
    echo "✓ Updated delivery coordinates:\n";
    echo "Tracking: TRK-69D61305C42FD\n";
    echo "Latitude: " . $delivery->latitude . "\n";
    echo "Longitude: " . $delivery->longitude . "\n";
    echo "Address: " . $delivery->delivery_address . "\n";
} else {
    echo "Delivery not found\n";
}
