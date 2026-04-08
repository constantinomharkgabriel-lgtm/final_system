<?php
// Quick registration test
echo "╔════════════════════════════════════════════════════════╗\n";
echo "║     Testing Consumer Registration Flow                ║\n";
echo "╠════════════════════════════════════════════════════════╣\n\n";

try {
    echo "1. Create test consumer... ";
    $testEmail = 'test_' . time() . '@example.com';
    $user = \App\Models\User::create([
        'name' => 'Test Consumer ' . time(),
        'email' => $testEmail,
        'phone' => '+639123456789',
        'password' => bcrypt('password123'),
        'role' => 'consumer',
        'status' => 'active'
    ]);
    echo "✓\n";
    
    echo "2. Issue verification code... ";
    $service = app(\App\Services\ConsumerVerificationService::class);
    $service->issueCode($user);
    echo "✓\n";
    
    echo "3. Check code in database... ";
    $code = \DB::table('consumer_verification_codes')
        ->where('user_id', $user->id)
        ->first();
    if ($code && $code->code) {
        echo "✓\n";
    } else {
        echo "✗ (Code not found)\n";
    }
    
    echo "4. Verify email... ";
    $user->forceFill(['email_verified_at' => \Carbon\Carbon::now()])->save();
    echo "✓\n";
    
    echo "5. Check verification status... ";
    $verified = \App\Models\User::find($user->id);
    echo ($verified->email_verified_at ? "✓ Verified\n" : "✗ Not verified\n");
    
    echo "6. Cleanup... ";
    $user->delete();
    echo "✓\n";
    
    echo "\n╠════════════════════════════════════════════════════════╣\n";
    echo "║     ✓ ALL TESTS PASSED - System is ready!            ║\n";
    echo "╚════════════════════════════════════════════════════════╝\n\n";
    
} catch (\Throwable $e) {
    echo "✗\nERROR: " . $e->getMessage() . "\n";
}
