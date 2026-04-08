<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\FarmOwner;
use App\Rules\PhilippinePhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DriverController extends Controller
{
    use \App\Http\Controllers\Concerns\ResolvesFarmOwner;

    /**
     * Determine if the current user is accessing from logistics portal
     */
    protected function isLogisticsPortal(): bool
    {
        return Auth::user()->role === 'logistics';
    }

    /**
     * Get the correct view path based on the current portal
     */
    protected function viewPath(string $view): string
    {
        $portal = $this->isLogisticsPortal() ? 'logistics' : 'farmowner';
        return "$portal.$view";
    }

    public function index(Request $request)
    {
        $farmOwner = $this->getFarmOwner();
        
        $query = Driver::byFarmOwner($farmOwner->id)
            ->select('id', 'name', 'phone', 'vehicle_type', 'vehicle_plate', 'license_expiry', 'status', 'rating', 'is_verified', 'verified_at');

        // Show both verified and unverified - farm owner can approve from list
        // Only show unverified if there's a filter param
        if ($request->filled('verified')) {
            $isVerified = filter_var($request->verified, FILTER_VALIDATE_BOOLEAN);
            if ($isVerified) {
                $query->verified();
            } else {
                $query->unverified();
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $drivers = $query->orderBy('name')->paginate(20);

        // Also get pending verification count for admin notification
        $pending_verification = Driver::byFarmOwner($farmOwner->id)
            ->unverified()
            ->count();

        $stats = [
            'total' => Driver::byFarmOwner($farmOwner->id)->count(),
            'verified' => Driver::byFarmOwner($farmOwner->id)->verified()->count(),
            'pending_verification' => $pending_verification,
            'available' => Driver::byFarmOwner($farmOwner->id)->verified()->available()->count(),
            'on_delivery' => Driver::byFarmOwner($farmOwner->id)->verified()->where('status', 'on_delivery')->count(),
        ];

        return view($this->viewPath('drivers.index'), compact('drivers', 'stats', 'pending_verification'));
    }

    public function create()
    {
        return view($this->viewPath('drivers.create'));
    }

    public function store(Request $request)
    {
        $farmOwner = $this->getFarmOwner();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => ['required', new PhilippinePhoneNumber(), 'unique:drivers,phone'],
            'license_number' => 'nullable|string|max:50',
            'license_expiry' => 'nullable|date',
            'vehicle_type' => 'required|in:motorcycle,tricycle,van,truck,pickup',
            'vehicle_plate' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
        ]);

        $validated['farm_owner_id'] = $farmOwner->id;
        
        // Generate unique driver code
        $validated['driver_code'] = 'DRV-' . $farmOwner->id . '-' . time() . '-' . strtoupper(substr(uniqid(), -6));

        Driver::create($validated);

        return redirect()->route('drivers.index')->with('success', 'Driver added.');
    }

    public function show(Driver $driver)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($driver->farm_owner_id !== $farmOwner->id, 403);

        // Reload driver to ensure all columns are loaded
        $driver = Driver::find($driver->id);
        $driver->load(['deliveries' => fn($q) => $q->latest('created_at')->limit(20)]);

        $stats = [
            'total_deliveries' => $driver->total_deliveries,
            'completed_deliveries' => $driver->completed_deliveries ?? 0,
            'success_rate' => $driver->total_deliveries > 0 
                ? round(($driver->completed_deliveries / $driver->total_deliveries) * 100, 1) 
                : 0,
        ];

        return view($this->viewPath('drivers.show'), compact('driver', 'stats'));
    }

    public function edit(Driver $driver)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($driver->farm_owner_id !== $farmOwner->id, 403);

        return view($this->viewPath('drivers.edit'), compact('driver'));
    }

    public function update(Request $request, Driver $driver)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($driver->farm_owner_id !== $farmOwner->id, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'license_number' => 'nullable|string|max:50',
            'license_expiry' => 'nullable|date',
            'vehicle_type' => 'required|in:motorcycle,tricycle,van,truck,pickup',
            'vehicle_plate' => 'nullable|string|max:20',
            'status' => 'required|in:available,on_delivery,off_duty,suspended',
            'notes' => 'nullable|string',
        ]);

        $driver->update($validated);

        return redirect()->route('drivers.show', $driver)->with('success', 'Driver updated.');
    }

    public function destroy(Driver $driver)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($driver->farm_owner_id !== $farmOwner->id, 403);

        $driver->delete();

        $redirectRoute = $this->isLogisticsPortal() ? 'logistics.drivers.index' : 'drivers.index';
        return redirect()->route($redirectRoute)->with('success', 'Driver removed.');
    }
}
