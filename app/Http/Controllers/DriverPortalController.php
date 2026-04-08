<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Delivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DriverPortalController extends Controller
{
    /**
     * Driver Dashboard
     */
    public function dashboard()
    {
        $user = Auth::user();
        $driver = $user->driver;

        // Verify driver is verified
        if (!$driver || !$driver->is_verified) {
            return redirect()->route('driver.verification.pending');
        }

        // Stats
        $stats = [
            'pending_deliveries' => $driver->deliveries()->where('status', 'pending')->count(),
            'active_deliveries' => $driver->deliveries()->where('status', 'on_delivery')->count(),
            'completed_deliveries' => $driver->deliveries()->where('status', 'completed')->count(),
            'total_earnings' => $driver->total_earnings ?? 0,
            'rating' => $driver->rating ?? 0,
        ];

        // Recent deliveries
        $recentDeliveries = $driver->deliveries()
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('driver.dashboard', compact('driver', 'stats', 'recentDeliveries'));
    }

    /**
     * Driver Profile
     */
    public function profile()
    {
        $user = Auth::user();
        $driver = $user->driver;

        if (!$driver || !$driver->is_verified) {
            return redirect()->route('driver.verification.pending');
        }

        return view('driver.profile', compact('driver', 'user'));
    }

    /**
     * List all deliveries available and assigned to driver
     */
    public function deliveries(Request $request)
    {
        $user = Auth::user();
        $driver = $user->driver;

        if (!$driver || !$driver->is_verified) {
            return redirect()->route('driver.verification.pending');
        }

        $query = $driver->deliveries();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $deliveries = $query->orderByDesc('created_at')->paginate(15);

        // Stats
        $stats = [
            'total' => $driver->deliveries()->count(),
            'pending' => $driver->deliveries()->where('status', 'pending')->count(),
            'accepted' => $driver->deliveries()->where('status', 'accepted')->count(),
            'on_delivery' => $driver->deliveries()->where('status', 'on_delivery')->count(),
            'completed' => $driver->deliveries()->where('status', 'completed')->count(),
            'failed' => $driver->deliveries()->where('status', 'failed')->count(),
        ];

        return view('driver.deliveries.index', compact('deliveries', 'stats'));
    }

    /**
     * Show delivery details
     */
    public function showDelivery(Delivery $delivery)
    {
        $user = Auth::user();
        $driver = $user->driver;

        if (!$driver || !$driver->is_verified) {
            return redirect()->route('driver.verification.pending');
        }

        // Check if delivery belongs to this driver
        if ($delivery->driver_id !== $driver->id) {
            abort(403, 'Unauthorized access to this delivery.');
        }

        return view('driver.deliveries.show', compact('delivery', 'driver'));
    }

    /**
     * Accept a delivery
     */
    public function acceptDelivery(Request $request, Delivery $delivery)
    {
        $user = Auth::user();
        $driver = $user->driver;

        if (!$driver || !$driver->is_verified) {
            return redirect()->route('driver.verification.pending');
        }

        // Check if delivery is assigned and pending
        if ($delivery->driver_id !== $driver->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($delivery->status !== 'pending') {
            return response()->json(['error' => 'Delivery is not pending'], 400);
        }

        $delivery->update(['status' => 'accepted']);

        Log::info('Driver accepted delivery', [
            'driver_id' => $driver->id,
            'delivery_id' => $delivery->id,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Delivery accepted successfully.']);
        }

        return back()->with('success', 'Delivery accepted! You can now start the delivery.');
    }

    /**
     * Reject a delivery
     */
    public function rejectDelivery(Request $request, Delivery $delivery)
    {
        $user = Auth::user();
        $driver = $user->driver;

        if (!$driver || !$driver->is_verified) {
            return redirect()->route('driver.verification.pending');
        }

        // Check if delivery belongs to this driver
        if ($delivery->driver_id !== $driver->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!in_array($delivery->status, ['pending', 'accepted'])) {
            return response()->json(['error' => 'Cannot reject delivery in current status'], 400);
        }

        $delivery->update([
            'status' => 'pending',
            'driver_id' => null,
        ]);

        Log::info('Driver rejected delivery', [
            'driver_id' => $driver->id,
            'delivery_id' => $delivery->id,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Delivery rejected. It will be offered to other drivers.']);
        }

        return back()->with('success', 'Delivery rejected. It will be offered to other drivers.');
    }

    /**
     * Start delivery (mark as out for delivery)
     */
    public function startDelivery(Request $request, Delivery $delivery)
    {
        $user = Auth::user();
        $driver = $user->driver;

        if (!$driver || !$driver->is_verified) {
            return redirect()->route('driver.verification.pending');
        }

        // Check if delivery belongs to this driver
        if ($delivery->driver_id !== $driver->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($delivery->status !== 'accepted') {
            return response()->json(['error' => 'Delivery is not in accepted status'], 400);
        }

        $delivery->update(['status' => 'on_delivery']);
        $driver->update(['status' => 'on_delivery']);

        Log::info('Driver started delivery', [
            'driver_id' => $driver->id,
            'delivery_id' => $delivery->id,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Delivery started!']);
        }

        return back()->with('success', 'Delivery started! You are now on delivery.');
    }

    /**
     * Complete delivery
     */
    public function completeDelivery(Request $request, Delivery $delivery)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
            'proof_image' => 'nullable|image|max:5120',
        ]);

        $user = Auth::user();
        $driver = $user->driver;

        if (!$driver || !$driver->is_verified) {
            return redirect()->route('driver.verification.pending');
        }

        // Check if delivery belongs to this driver
        if ($delivery->driver_id !== $driver->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($delivery->status !== 'on_delivery') {
            return response()->json(['error' => 'Delivery is not in on_delivery status'], 400);
        }

        // Handle proof image
        if ($request->hasFile('proof_image')) {
            $path = $request->file('proof_image')->store('deliveries/proofs', 'public');
            $delivery->proof_image = $path;
        }

        $delivery->update([
            'status' => 'completed',
            'completed_at' => now(),
            'notes' => $validated['notes'] ?? $delivery->notes,
        ]);

        // Update driver status if no more active deliveries
        if ($driver->deliveries()->where('status', 'on_delivery')->count() === 0) {
            $driver->update(['status' => 'available']);
        }

        // Add earnings
        $driver->increment('total_earnings', $delivery->delivery_fee ?? 0);
        $driver->increment('completed_deliveries');

        Log::info('Driver completed delivery', [
            'driver_id' => $driver->id,
            'delivery_id' => $delivery->id,
            'fee' => $delivery->delivery_fee,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Delivery completed! ₱' . ($delivery->delivery_fee ?? 0) . ' added to your earnings.']);
        }

        return back()->with('success', 'Delivery completed! ₱' . ($delivery->delivery_fee ?? 0) . ' has been added to your earnings.');
    }

    /**
     * View earnings
     */
    public function earnings()
    {
        $user = Auth::user();
        $driver = $user->driver;

        if (!$driver || !$driver->is_verified) {
            return redirect()->route('driver.verification.pending');
        }

        $stats = [
            'total_earnings' => $driver->total_earnings ?? 0,
            'completed_deliveries' => $driver->completed_deliveries ?? 0,
            'average_per_delivery' => $driver->completed_deliveries > 0
                ? round(($driver->total_earnings ?? 0) / $driver->completed_deliveries, 2)
                : 0,
            'pending_earnings' => $driver->deliveries()
                ->where('status', 'on_delivery')
                ->sum('delivery_fee') ?? 0,
        ];

        // Earnings breakdown by month
        $earningsHistory = $driver->deliveries()
            ->where('status', 'completed')
            ->orderByDesc('completed_at')
            ->limit(30)
            ->get();

        return view('driver.earnings', compact('driver', 'stats', 'earningsHistory'));
    }
}
