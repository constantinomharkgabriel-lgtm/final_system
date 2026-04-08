<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class DriverVerificationController extends Controller
{
    /**
     * Verify driver email (from email link)
     */
    public function verify(Request $request)
    {
        $driver = Driver::findOrFail($request->driver);

        // Verify the hash matches the email
        if (sha1($driver->email) !== $request->hash) {
            return redirect('/')->with('error', 'Invalid verification link.');
        }

        // If already verified, show message and redirect to login
        if ($driver->is_verified) {
            return redirect()->route('login')
                ->with('info', 'Your email is already verified! Please log in to access your driver portal.');
        }

        // Mark as verified
        $driver->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        Log::info('Driver email verified', [
            'driver_id' => $driver->id,
            'email' => $driver->email,
        ]);

        // Fire verified event for audit trail
        event(new Verified($driver->user));

        // Redirect to login page with success message
        // Driver will log in and then be redirected to driver portal
        return redirect()->route('login')
            ->with('success', 'Email verified successfully! Your driver account is now active. Please log in to access your driver portal and start accepting deliveries.');
    }

    /**
     * Resend verification email
     */
    public function resend(Request $request)
    {
        // This could be called from unsent verification page
        // Get driver from current user
        $driver = auth()->user()->driver;

        if (!$driver) {
            return back()->with('error', 'Driver profile not found.');
        }

        if ($driver->is_verified) {
            return back()->with('info', 'Your email is already verified.');
        }

        try {
            $driver->notify(new \App\Notifications\VerifyDriverEmail($driver));
            return back()->with('success', 'Verification email sent. Please check your inbox.');
        } catch (\Throwable $e) {
            Log::error('Failed to resend driver verification email', [
                'driver_id' => $driver->id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Failed to send verification email. Please try again later.');
        }
    }
}
