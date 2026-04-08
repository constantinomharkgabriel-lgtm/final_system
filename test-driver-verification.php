<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Driver;
use App\Models\Employee;
use App\Models\User;
use App\Models\FarmOwner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║  DRIVER VERIFICATION EMAIL FLOW TEST                      ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Get a farm owner for testing
$farmOwner = FarmOwner::with('user')->first();
if (!$farmOwner) {
    echo "❌ No farm owner found. Please create one first.\n";
    exit;
}

$farmOwnerName = $farmOwner->user->name ?? $farmOwner->farm_name ?? 'Unknown';
echo "📍 Using Farm Owner: $farmOwnerName (Farm: {$farmOwner->farm_name})\n\n";

// Test 1: Check current driver count before adding
echo "─────────────────────────────────────────────────────────────\n";
echo "TEST 1: Current Driver Status\n";
echo "─────────────────────────────────────────────────────────────\n";

$totalDrivers = Driver::byFarmOwner($farmOwner->id)->count();
$verifiedDrivers = Driver::byFarmOwner($farmOwner->id)->verified()->count();
$unverifiedDrivers = Driver::byFarmOwner($farmOwner->id)->unverified()->count();

echo "✓ Total drivers: $totalDrivers\n";
echo "✓ Verified (visible in logistics): $verifiedDrivers\n";
echo "✓ Unverified (awaiting email verification): $unverifiedDrivers\n\n";

// Test 2: Check if any unverified drivers exist
if ($unverifiedDrivers > 0) {
    echo "─────────────────────────────────────────────────────────────\n";
    echo "TEST 2: Unverified Drivers Found\n";
    echo "─────────────────────────────────────────────────────────────\n";
    
    $unverifiedDriver = Driver::byFarmOwner($farmOwner->id)->unverified()->first();
    
    echo "📋 Unverified Driver Details:\n";
    echo "  • Name: {$unverifiedDriver->name}\n";
    echo "  • Email: {$unverifiedDriver->email}\n";
    echo "  • Status: {$unverifiedDriver->status}\n";
    echo "  • Is Verified: " . ($unverifiedDriver->is_verified ? '✓ Yes' : '✗ No') . "\n";
    echo "  • Verified At: " . ($unverifiedDriver->verified_at ? $unverifiedDriver->verified_at->format('Y-m-d H:i:s') : 'Pending') . "\n";
    echo "  • Created: " . $unverifiedDriver->created_at->format('Y-m-d H:i:s') . "\n\n";
    
    // Test 3: Generate verification URL
    echo "─────────────────────────────────────────────────────────────\n";
    echo "TEST 3: Verification URL Generation\n";
    echo "─────────────────────────────────────────────────────────────\n";
    
    $verificationHash = sha1($unverifiedDriver->email);
    $verificationUrl = route('driver.email.verify', [
        'driver' => $unverifiedDriver->id,
        'hash' => $verificationHash,
    ]);
    
    echo "🔗 Verification URL:\n";
    echo "   $verificationUrl\n\n";
    
    // Test 4: Simulate email verification (click the link)
    echo "─────────────────────────────────────────────────────────────\n";
    echo "TEST 4: Simulating Email Verification\n";
    echo "─────────────────────────────────────────────────────────────\n";
    
    // Mark as verified
    $unverifiedDriver->update([
        'is_verified' => true,
        'verified_at' => now(),
    ]);
    
    echo "✓ Driver marked as verified\n";
    echo "✓ Verification timestamp: " . $unverifiedDriver->fresh()->verified_at->format('Y-m-d H:i:s') . "\n\n";
    
    // Test 5: Check if driver now appears in logistics
    echo "─────────────────────────────────────────────────────────────\n";
    echo "TEST 5: Verified Driver Visibility in Logistics\n";
    echo "─────────────────────────────────────────────────────────────\n";
    
    $verifiedInLogistics = Driver::byFarmOwner($farmOwner->id)->verified()->count();
    echo "✓ Verified drivers now in logistics: $verifiedInLogistics\n";
    
    if ($verifiedInLogistics > 0) {
        echo "✓ Driver is now visible to logistics staff!\n";
    } else {
        echo "❌ Driver NOT visible to logistics staff\n";
    }
    
} else {
    echo "⚠️  No unverified drivers found. Adding a test driver...\n\n";
    
    // Create a test user and driver
    echo "─────────────────────────────────────────────────────────────\n";
    echo "TEST 2: Creating Test Driver\n";
    echo "─────────────────────────────────────────────────────────────\n";
    
    $testUser = User::create([
        'name' => 'Test Driver ' . time(),
        'email' => 'test-driver-' . time() . '@example.com',
        'password' => \Illuminate\Support\Facades\Hash::make('password123'),
        'role' => 'driver',
        'status' => 'active',
    ]);
    
    echo "✓ Test User Created:\n";
    echo "  • Name: {$testUser->name}\n";
    echo "  • Email: {$testUser->email}\n";
    echo "  • ID: {$testUser->id}\n\n";
    
    $testDriver = Driver::create([
        'farm_owner_id' => $farmOwner->id,
        'user_id' => $testUser->id,
        'driver_code' => 'TEST-DRV-' . time(),
        'name' => $testUser->name,
        'email' => $testUser->email,
        'phone' => '0912' . rand(10000000, 99999999),
        'vehicle_type' => 'motorcycle',
        'vehicle_plate' => 'TEST-00' . rand(100, 999),
        'status' => 'available',
        'is_verified' => false,
    ]);
    
    echo "✓ Test Driver Created:\n";
    echo "  • Name: {$testDriver->name}\n";
    echo "  • Email: {$testDriver->email}\n";
    echo "  • ID: {$testDriver->id}\n";
    echo "  • Is Verified: ✗ No\n\n";
    
    // Generate verification URL
    echo "─────────────────────────────────────────────────────────────\n";
    echo "TEST 3: Verification URL Generation\n";
    echo "─────────────────────────────────────────────────────────────\n";
    
    $verificationHash = sha1($testDriver->email);
    $verificationUrl = route('driver.email.verify', [
        'driver' => $testDriver->id,
        'hash' => $verificationHash,
    ]);
    
    echo "🔗 Verification URL:\n";
    echo "   $verificationUrl\n\n";
    
    // Simulate verification
    echo "─────────────────────────────────────────────────────────────\n";
    echo "TEST 4: Simulating Email Verification\n";
    echo "─────────────────────────────────────────────────────────────\n";
    
    $testDriver->update([
        'is_verified' => true,
        'verified_at' => now(),
    ]);
    
    echo "✓ Driver marked as verified\n";
    echo "✓ Verification timestamp: " . $testDriver->fresh()->verified_at->format('Y-m-d H:i:s') . "\n\n";
    
    // Check visibility
    echo "─────────────────────────────────────────────────────────────\n";
    echo "TEST 5: Verified Driver Visibility\n";
    echo "─────────────────────────────────────────────────────────────\n";
    
    $visibleInLogistics = Driver::byFarmOwner($farmOwner->id)->verified()->where('id', $testDriver->id)->exists();
    if ($visibleInLogistics) {
        echo "✓ Test driver is now visible to logistics staff!\n";
    } else {
        echo "❌ Test driver NOT visible to logistics staff\n";
    }
}

// Final Summary
echo "\n─────────────────────────────────────────────────────────────\n";
echo "FINAL STATUS\n";
echo "─────────────────────────────────────────────────────────────\n";

$totalDriversFinal = Driver::byFarmOwner($farmOwner->id)->count();
$verifiedFinal = Driver::byFarmOwner($farmOwner->id)->verified()->count();
$unverifiedFinal = Driver::byFarmOwner($farmOwner->id)->unverified()->count();

echo "📊 Final Counts:\n";
echo "  • Total drivers: $totalDriversFinal\n";
echo "  • Verified (visible in logistics): $verifiedFinal\n";
echo "  • Unverified (awaiting verification): $unverifiedFinal\n\n";

// Test verification email sending capability
echo "─────────────────────────────────────────────────────────────\n";
echo "TEST 6: Email Configuration Check\n";
echo "─────────────────────────────────────────────────────────────\n";

$mailDriver = config('mail.default');
echo "📧 Mail Driver: $mailDriver\n";

if ($mailDriver === 'log') {
    echo "⚠️  Mail is in LOG mode - check storage/logs/laravel.log for verification emails\n";
} elseif ($mailDriver === 'array' || $mailDriver === 'null') {
    echo "⚠️  Mail is in " . strtoupper($mailDriver) . " mode - emails won't be sent\n";
} else {
    echo "✓ Mail is configured for production\n";
}

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║  TEST COMPLETE                                             ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";
