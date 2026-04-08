<?php

namespace App\Services;

use App\Models\ConsumerVerificationCode;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ConsumerVerificationService
{
    public function issueCode(User $user): void
    {
        $code = (string) random_int(100000, 999999);

        // Create verification code in database
        ConsumerVerificationCode::updateOrCreate(
            ['user_id' => $user->id],
            [
                'code' => $code,
                'expires_at' => now()->addMinutes(10),
                'attempts' => 0,
            ]
        );

        Log::info('Verification code created', [
            'user_id' => $user->id,
            'email' => $user->email,
            'code' => $code,
            'expires_at' => now()->addMinutes(10),
        ]);

        // Send verification email synchronously
        try {
            Mail::raw(
                "Your Poultry Consumer verification code is: {$code}\n\nThis code expires in 10 minutes.\n\nIf you did not request this code, please ignore this email.",
                function ($message) use ($user): void {
                    $message->to($user->email, $user->name)
                        ->subject('Your Consumer Verification Code - Poultry System')
                        ->from(config('mail.from.address'), config('mail.from.name'));
                }
            );
            Log::info('Verification email sent successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send verification email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            // Log a fallback message showing the verification code
            Log::warning('FALLBACK: Verification code for manual verification', [
                'user_id' => $user->id,
                'email' => $user->email,
                'code' => $code,
                'description' => 'Email sending failed - use this code for testing',
            ]);
            // Re-throw so controller can handle it
            throw $e;
        }
    }
}
