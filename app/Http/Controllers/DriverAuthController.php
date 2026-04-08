<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class DriverAuthController extends Controller
{
    /**
     * Show driver login form
     */
    public function showLogin()
    {
        return view('driver.auth.login');
    }

    /**
     * Handle driver login
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        // Find user with driver role
        $user = User::where('email', $validated['email'])
            ->where('role', 'driver')
            ->first();

        if (!$user) {
            Log::warning('Failed driver login attempt - user not found', [
                'email' => $validated['email'],
            ]);
            return back()->withErrors([
                'email' => 'No driver account found with that email.',
            ]);
        }

        // Check password
        if (!Hash::check($validated['password'], $user->password)) {
            Log::warning('Failed driver login attempt - invalid password', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
            return back()->withErrors([
                'password' => 'Invalid password.',
            ]);
        }

        // Check if user has driver profile
        $driver = $user->driver;
        if (!$driver) {
            Log::warning('Driver user without driver profile', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
            return back()->with('error', 'Driver profile not found. Please contact support.');
        }

        // Check if email is verified
        if (!$driver->is_verified) {
            Log::info('Unverified driver login attempt', [
                'driver_id' => $driver->id,
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
            Auth::login($user);
            return redirect()->route('driver.verification.pending')
                ->with('info', 'Your email is not yet verified. Please check your inbox for the verification link.');
        }

        // Login successful
        Auth::login($user);
        
        Log::info('Driver login successful', [
            'driver_id' => $driver->id,
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return redirect()->route('driver.dashboard')
            ->with('success', 'Welcome back! Ready to accept deliveries.');
    }

    /**
     * Handle driver logout
     */
    public function logout(Request $request)
    {
        $driver = Auth::user()->driver;
        
        Log::info('Driver logged out', [
            'driver_id' => $driver?->id,
            'user_id' => Auth::id(),
            'email' => Auth::user()->email,
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'You have been logged out successfully.');
    }
}
