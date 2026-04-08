<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\FarmOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DriverVerificationAdminController extends Controller
{
    /**
     * List all unverified drivers for a farm owner
     */
    public function pendingDrivers(Request $request)
    {
        $farmOwner = Auth::user()->isFarmOwner() 
            ? Auth::user()->farmOwner 
            : FarmOwner::findOrFail($request->farm_owner_id ?? 1);

        $drivers = Driver::byFarmOwner($farmOwner->id)
            ->unverified()
            ->with(['employee', 'user'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.drivers.pending', compact('drivers', 'farmOwner'));
    }

    /**
     * Show driver details for verification review
     */
    public function reviewDriver(Driver $driver)
    {
        abort_if($driver->farmOwner->user_id !== Auth::id() && !Auth::user()->isSuperAdmin(), 403);

        return view('admin.drivers.review', compact('driver'));
    }

    /**
     * Approve driver and mark as verified
     */
    public function approveDriver(Request $request, Driver $driver)
    {
        abort_if($driver->farmOwner->user_id !== Auth::id() && !Auth::user()->isSuperAdmin(), 403);

        $driver->update([
            'is_verified' => true,
            'verified_at' => now(),
            'status' => 'available',
        ]);

        Log::info('Driver verified', [
            'driver_id' => $driver->id,
            'driver_name' => $driver->name,
            'farm_owner_id' => $driver->farm_owner_id,
            'verified_by' => Auth::id(),
        ]);

        // Fire event to notify driver
        event(new \App\Events\DriverVerified($driver));

        return redirect()->back()
            ->with('success', "Driver {$driver->name} has been verified and is now visible to customers.");
    }

    /**
     * Reject driver
     */
    public function rejectDriver(Request $request, Driver $driver)
    {
        abort_if($driver->farmOwner->user_id !== Auth::id() && !Auth::user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:255',
        ]);

        $driver->update([
            'is_verified' => false,
            'status' => 'inactive',
        ]);

        Log::warning('Driver rejected', [
            'driver_id' => $driver->id,
            'driver_name' => $driver->name,
            'reason' => $validated['rejection_reason'],
            'rejected_by' => Auth::id(),
        ]);

        // Notify driver of rejection
        $driver->user?->notify(new \App\Notifications\DriverRejected($driver, $validated['rejection_reason']));

        return redirect()->back()
            ->with('warning', "Driver {$driver->name} has been rejected.");
    }

    /**
     * List verified drivers for logistics staff
     */
    public function verifiedDrivers(Request $request)
    {
        $farmOwner = $this->getFarmOwner();

        $drivers = Driver::byFarmOwner($farmOwner->id)
            ->verified()  // Only verified drivers
            ->with(['employee', 'user'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.drivers.verified', compact('drivers'));
    }

    private function getFarmOwner(): FarmOwner
    {
        if (Auth::user()?->isFarmOwner()) {
            return Auth::user()->farmOwner;
        }

        return Auth::user()->farmOwner ?? FarmOwner::first();
    }
}
