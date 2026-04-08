# 🔨 DRIVER PORTAL - IMPLEMENTATION GUIDE

## Step-by-Step Setup

---

## PHASE 1: Database & Models

### Step 1.1: Create Driver Model (Independent)

```php
// app/Models/Driver.php

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    protected $fillable = [
        'user_id',
        'employee_id',
        'driver_code',
        'name',
        'phone',
        'email',
        'vehicle_type',
        'vehicle_plate',
        'vehicle_model',
        'license_number',
        'license_expiry',
        'delivery_fee',
        'status',
        'total_earnings',
        'total_deliveries',
        'average_rating',
        'is_verified',
    ];

    protected $casts = [
        'delivery_fee' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'license_expiry' => 'date',
        'is_verified' => 'boolean',
        'last_active_at' => 'datetime',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    // ========== RELATIONSHIPS ==========

    /**
     * Driver belongs to a user account (independent login)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Driver can optionally link to employee (for payroll)
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Driver has many task assignments
     */
    public function taskAssignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class);
    }

    /**
     * Driver has many deliveries (via task assignments)
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class, 'driver_id');
    }

    /**
     * Driver earnings history
     */
    public function earnings(): HasMany
    {
        return $this->hasMany(DriverEarning::class);
    }

    /**
     * Driver proofs (photos, signatures)
     */
    public function proofs(): HasMany
    {
        return $this->hasMany(DeliveryProof::class);
    }

    // ========== SCOPES ==========

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeTopRated($query)
    {
        return $query->orderBy('average_rating', 'desc');
    }

    // ========== HELPER METHODS ==========

    /**
     * Get driver's today earnings
     */
    public function getTodayEarnings()
    {
        return $this->earnings()
            ->whereDate('created_at', today())
            ->sum('actual_earning');
    }

    /**
     * Get driver's month earnings
     */
    public function getMonthEarnings()
    {
        return $this->earnings()
            ->whereMonth('created_at', now()->month)
            ->sum('actual_earning');
    }

    /**
     * Check if driver is online and available
     */
    public function isAvailable(): bool
    {
        return $this->status === 'available' && 
               $this->is_verified && 
               $this->last_active_at > now()->subMinutes(5);
    }

    /**
     * Get active task count
     */
    public function activeTaskCount(): int
    {
        return $this->taskAssignments()
            ->whereIn('status', ['accepted', 'en_route', 'arrived'])
            ->count();
    }

    /**
     * Update driver rating based on reviews
     */
    public function updateAverageRating()
    {
        $this->average_rating = $this->deliveries()
            ->whereNotNull('rating')
            ->avg('rating');
        $this->save();
    }
}
```

### Step 1.2: Create TaskAssignment Model

```php
// app/Models/TaskAssignment.php

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskAssignment extends Model
{
    protected $fillable = [
        'delivery_id',
        'driver_id',
        'assigned_by_user_id',
        'status',
        'accepted_at',
        'rejected_at',
        'rejection_reason',
        'started_at',
        'arrived_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'started_at' => 'datetime',
        'arrived_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // ========== RELATIONSHIPS ==========

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    public function proofs(): HasMany
    {
        return $this->hasMany(DeliveryProof::class);
    }

    public function paymentConfirmation()
    {
        return $this->hasOneThrough(
            PaymentConfirmation::class,
            Delivery::class,
            'id',
            'delivery_id',
            'delivery_id',
            'id'
        );
    }

    // ========== SCOPES ==========

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['accepted', 'en_route', 'arrived']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // ========== WORKFLOW METHODS ==========

    public function accept(): bool
    {
        $this->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        // Update driver status
        $this->driver->update(['status' => 'on_delivery']);

        // Send notification to consumer
        $this->notifyConsumer('Task accepted by driver');

        return true;
    }

    public function reject($reason): bool
    {
        $this->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        // Make driver available again
        $this->driver->update(['status' => 'available']);

        // Notify logistics to reassign
        $this->notifyLogistics('Driver rejected task');

        // Auto-assign to next driver
        event(new TaskRejected($this));

        return true;
    }

    public function markStarted(): bool
    {
        $this->update([
            'status' => 'en_route',
            'started_at' => now(),
        ]);

        return true;
    }

    public function markArrived($gps_lat, $gps_long): bool
    {
        $this->update([
            'status' => 'arrived',
            'arrived_at' => now(),
        ]);

        // Save GPS proof
        DeliveryProof::create([
            'delivery_id' => $this->delivery_id,
            'task_assignment_id' => $this->id,
            'proof_type' => 'gps_location',
            'gps_latitude' => $gps_lat,
            'gps_longitude' => $gps_long,
            'uploaded_by_driver_id' => $this->driver_id,
        ]);

        // Notify consumer
        $this->notifyConsumer('Driver has arrived');

        return true;
    }

    public function markComplete(): bool
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Update delivery status
        $this->delivery->update(['status' => 'delivered']);

        // Add commission to driver earnings
        DriverEarning::create([
            'driver_id' => $this->driver_id,
            'delivery_id' => $this->delivery_id,
            'base_delivery_fee' => $this->driver->delivery_fee,
            'bonus_multiplier' => $this->calculateBonus(),
            'actual_earning' => $this->driver->delivery_fee * $this->calculateBonus(),
            'status' => 'pending',
            'transaction_date' => now()->toDateString(),
        ]);

        return true;
    }

    protected function calculateBonus(): float
    {
        $hour = now()->hour;
        // Rush hour: 11-12 AM, 5-7 PM
        if (($hour >= 11 && $hour < 12) || ($hour >= 17 && $hour < 19)) {
            return 1.2; // 20% bonus
        }
        return 1.0;
    }

    protected function notifyConsumer($message)
    {
        // Send notification
    }

    protected function notifyLogistics($message)
    {
        // Send notification
    }
}
```

### Step 1.3: Create Migrations

```php
// database/migrations/[timestamp]_create_drivers_table.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->foreignId('employee_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->string('driver_code')->unique();
            $table->string('name');
            $table->string('phone', 20);
            $table->string('email')->unique();
            $table->string('vehicle_type');
            $table->string('vehicle_plate')->unique();
            $table->string('vehicle_model');
            $table->string('license_number')->unique();
            $table->date('license_expiry');
            $table->decimal('delivery_fee', 10, 2);
            $table->enum('status', ['available', 'on_delivery', 'unavailable', 'offline'])->default('offline');
            $table->decimal('total_earnings', 15, 2)->default(0);
            $table->integer('total_deliveries')->default(0);
            $table->integer('total_completed')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('email');
            $table->index(['is_verified', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
```

```php
// database/migrations/[timestamp]_create_task_assignments_table.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->unique()->constrained('deliveries')->onDelete('cascade');
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade');
            $table->foreignId('assigned_by_user_id')->constrained('users')->onDelete('cascade');
            
            // Status workflow
            $table->enum('status', ['pending', 'accepted', 'rejected', 'en_route', 'arrived', 'completed', 'cancelled'])
                ->default('pending');
            
            // Timestamps
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            // Rejection info
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();

            // Indexes
            $table->index('driver_id');
            $table->index('status');
            $table->index(['driver_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_assignments');
    }
};
```

```php
// database/migrations/[timestamp]_create_delivery_proofs_table.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained('deliveries')->onDelete('cascade');
            $table->foreignId('task_assignment_id')->constrained('task_assignments')->onDelete('cascade');
            $table->foreignId('uploaded_by_driver_id')->constrained('drivers')->onDelete('cascade');
            $table->foreignId('verified_by_logistics_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Proof type
            $table->enum('proof_type', ['photo', 'signature', 'receipt', 'gps_location']);
            
            // File paths
            $table->string('image_path')->nullable();
            $table->string('signature_path')->nullable();
            $table->string('receipt_path')->nullable();
            
            // GPS location
            $table->decimal('gps_latitude', 10, 8)->nullable();
            $table->decimal('gps_longitude', 11, 8)->nullable();
            $table->decimal('gps_accuracy', 5, 2)->nullable();
            
            // Comments
            $table->text('driver_comment')->nullable();
            
            // Verification
            $table->timestamp('verified_at')->nullable();
            
            $table->timestamps();

            // Indexes
            $table->index('delivery_id');
            $table->index('proof_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_proofs');
    }
};
```

```php
// database/migrations/[timestamp]_create_payment_confirmations_table.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->unique()->constrained('deliveries')->onDelete('cascade');
            
            // Payment amounts
            $table->decimal('order_amount', 15, 2);
            $table->decimal('delivery_fee', 10, 2);
            $table->decimal('total_amount', 15, 2);
            
            // Payment info
            $table->enum('payment_method', ['cash', 'online', 'check', 'credit']);
            $table->enum('payment_collected_by', ['driver', 'logistics', 'consumer', 'farm_owner'])
                ->default('driver');
            $table->string('collection_proof_photo')->nullable();
            
            // Confirmations
            $table->boolean('confirmed_by_driver')->default(false);
            $table->boolean('confirmed_by_consumer')->default(false);
            $table->boolean('confirmed_by_logistics')->default(false);
            
            // Timestamps
            $table->timestamp('expected_at')->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('delivery_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_confirmations');
    }
};
```

```php
// database/migrations/[timestamp]_create_driver_earnings_table.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade');
            $table->foreignId('delivery_id')->nullable()->constrained('deliveries')->onDelete('set null');
            
            // Earning breakdown
            $table->decimal('base_delivery_fee', 10, 2);
            $table->decimal('bonus_multiplier', 3, 2)->default(1.0);
            $table->decimal('actual_earning', 10, 2);
            
            // Status
            $table->enum('status', ['pending', 'confirmed', 'paid'])->default('pending');
            $table->date('transaction_date');
            $table->date('payment_date')->nullable();
            
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['driver_id', 'transaction_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_earnings');
    }
};
```

---

## PHASE 2: Controllers

### Step 2.1: DriverAuthController

```php
// app/Http/Controllers/Api/DriverAuthController.php

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class DriverAuthController extends Controller
{
    /**
     * Register a new driver
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:drivers,email',
            'password' => 'required|min:8|confirmed',
            'phone' => 'required|string|max:20|unique:drivers,phone',
            'vehicle_type' => 'required|string',
            'vehicle_plate' => 'required|string|unique:drivers,vehicle_plate',
            'vehicle_model' => 'required|string',
            'license_number' => 'required|string|unique:drivers,license_number',
            'license_expiry' => 'required|date',
            'delivery_fee' => 'required|numeric|min:10|max:500',
        ]);

        // Create user account for driver
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'driver',
            'status' => 'active',
        ]);

        // Generate unique driver code
        $driver_code = 'DRV' . date('Ymd') . str_pad($user->id, 5, '0', STR_PAD_LEFT);

        // Create driver profile
        $driver = Driver::create([
            'user_id' => $user->id,
            'driver_code' => $driver_code,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'vehicle_type' => $validated['vehicle_type'],
            'vehicle_plate' => $validated['vehicle_plate'],
            'vehicle_model' => $validated['vehicle_model'],
            'license_number' => $validated['license_number'],
            'license_expiry' => $validated['license_expiry'],
            'delivery_fee' => $validated['delivery_fee'],
            'status' => 'offline',
            'is_verified' => false, // Needs admin approval
        ]);

        return response()->json([
            'message' => 'Driver registered successfully. Awaiting verification.',
            'driver' => $driver,
            'token' => $user->createToken('driver-token')->plainTextToken,
        ], 201);
    }

    /**
     * Login driver
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->role !== 'driver') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $driver = $user->driver;

        // Update last active time
        $driver->update(['last_active_at' => now()]);

        return response()->json([
            'message' => 'Logged in successfully',
            'driver' => $driver,
            'token' => $user->createToken('driver-token')->plainTextToken,
        ]);
    }

    /**
     * Logout driver
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
```

### Step 2.2: DriverTaskController

```php
// app/Http/Controllers/Api/DriverTaskController.php

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaskAssignment;
use App\Models\DeliveryProof;
use Illuminate\Http\Request;

class DriverTaskController extends Controller
{
    /**
     * Get all tasks for driver
     */
    public function index(Request $request)
    {
        $driver = $request->user()->driver;

        $tasks = TaskAssignment::where('driver_id', $driver->id)
            ->with(['delivery' => function ($query) {
                $query->with(['order', 'paymentConfirmation']);
            }])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($tasks);
    }

    /**
     * Get pending (unaccepted) tasks
     */
    public function pending(Request $request)
    {
        $driver = $request->user()->driver;

        $tasks = TaskAssignment::where('driver_id', $driver->id)
            ->where('status', 'pending')
            ->with(['delivery.order'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json($tasks);
    }

    /**
     * Get active tasks
     */
    public function active(Request $request)
    {
        $driver = $request->user()->driver;

        $tasks = TaskAssignment::where('driver_id', $driver->id)
            ->whereIn('status', ['accepted', 'en_route', 'arrived'])
            ->with(['delivery.order'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json($tasks);
    }

    /**
     * Get single task detail
     */
    public function show(Request $request, TaskAssignment $task)
    {
        if ($task->driver_id !== $request->user()->driver->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $task->load(['delivery.order', 'proofs', 'paymentConfirmation']);

        return response()->json($task);
    }

    /**
     * Accept task assignment
     */
    public function accept(Request $request, TaskAssignment $task)
    {
        $driver = $request->user()->driver;

        if ($task->driver_id !== $driver->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($task->status !== 'pending') {
            return response()->json(['message' => 'Task must be pending to accept'], 400);
        }

        $task->accept();

        return response()->json([
            'message' => 'Task accepted successfully',
            'task' => $task->fresh(['delivery.order']),
        ]);
    }

    /**
     * Reject task assignment
     */
    public function reject(Request $request, TaskAssignment $task)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $driver = $request->user()->driver;

        if ($task->driver_id !== $driver->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($task->status !== 'pending') {
            return response()->json(['message' => 'Task must be pending to reject'], 400);
        }

        $task->reject($validated['reason']);

        return response()->json(['message' => 'Task rejected successfully']);
    }

    /**
     * Mark en route
     */
    public function start(Request $request, TaskAssignment $task)
    {
        $driver = $request->user()->driver;

        if ($task->driver_id !== $driver->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($task->status !== 'accepted') {
            return response()->json(['message' => 'Task must be accepted first'], 400);
        }

        $task->markStarted();

        return response()->json(['message' => 'En route', 'task' => $task]);
    }

    /**
     * Mark arrived with GPS proof
     */
    public function arrived(Request $request, TaskAssignment $task)
    {
        $validated = $request->validate([
            'gps_latitude' => 'required|numeric',
            'gps_longitude' => 'required|numeric',
            'gps_accuracy' => 'nullable|numeric',
        ]);

        $driver = $request->user()->driver;

        if ($task->driver_id !== $driver->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $task->markArrived(
            $validated['gps_latitude'],
            $validated['gps_longitude']
        );

        return response()->json(['message' => 'Marked arrived', 'task' => $task]);
    }

    /**
     * Complete delivery task
     */
    public function complete(Request $request, TaskAssignment $task)
    {
        $driver = $request->user()->driver;

        if ($task->driver_id !== $driver->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!in_array($task->status, ['arrived', 'en_route'])) {
            return response()->json(['message' => 'Invalid task status'], 400);
        }

        $task->markComplete();

        return response()->json([
            'message' => 'Delivery completed successfully',
            'earnings' => $driver->getTodayEarnings(),
        ]);
    }

    /**
     * Upload delivery proof
     */
    public function uploadProof(Request $request, TaskAssignment $task)
    {
        $validated = $request->validate([
            'proof_type' => 'required|in:photo,signature,receipt',
            'image' => 'required_if:proof_type,photo|image|max:5120',
            'signature' => 'required_if:proof_type,signature|string',
            'driver_comment' => 'nullable|string',
        ]);

        $driver = $request->user()->driver;

        if ($task->driver_id !== $driver->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $proof_data = [
            'delivery_id' => $task->delivery_id,
            'task_assignment_id' => $task->id,
            'proof_type' => $validated['proof_type'],
            'uploaded_by_driver_id' => $driver->id,
            'driver_comment' => $validated['driver_comment'] ?? null,
        ];

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('delivery-proofs', 'public');
            $proof_data['image_path'] = $path;
        }

        if ($validated['proof_type'] === 'signature') {
            $path = $request->file('signature')->store('delivery-signatures', 'public');
            $proof_data['signature_path'] = $path;
        }

        $proof = DeliveryProof::create($proof_data);

        return response()->json([
            'message' => 'Proof uploaded successfully',
            'proof' => $proof,
        ]);
    }
}
```

---

## PHASE 3: Routes

Add to `routes/api.php`:

```php
// Driver Authentication Routes
Route::prefix('drivers')->group(function () {
    Route::post('/auth/register', [DriverAuthController::class, 'register']);
    Route::post('/auth/login', [DriverAuthController::class, 'login']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [DriverAuthController::class, 'logout']);

        // Task Management
        Route::get('/tasks', [DriverTaskController::class, 'index']);
        Route::get('/tasks/pending', [DriverTaskController::class, 'pending']);
        Route::get('/tasks/active', [DriverTaskController::class, 'active']);
        Route::get('/tasks/{task}', [DriverTaskController::class, 'show']);
        Route::post('/tasks/{task}/accept', [DriverTaskController::class, 'accept']);
        Route::post('/tasks/{task}/reject', [DriverTaskController::class, 'reject']);
        Route::post('/tasks/{task}/start', [DriverTaskController::class, 'start']);
        Route::post('/tasks/{task}/arrived', [DriverTaskController::class, 'arrived']);
        Route::post('/tasks/{task}/complete', [DriverTaskController::class, 'complete']);
        Route::post('/tasks/{task}/proof', [DriverTaskController::class, 'uploadProof']);

        // Profile
        Route::get('/me', [DriverProfileController::class, 'show']);
        Route::put('/me', [DriverProfileController::class, 'update']);
        Route::put('/me/availability', [DriverProfileController::class, 'updateAvailability']);

        // Earnings
        Route::get('/earnings/today', [DriverEarningsController::class, 'today']);
        Route::get('/earnings/month', [DriverEarningsController::class, 'month']);
        Route::get('/earnings/history', [DriverEarningsController::class, 'history']);
    });
});
```

---

## PHASE 4: Frontend - Driver Portal Vue/React Component

```vue
<!-- resources/js/components/DriverPortal.vue -->

<template>
  <div class="driver-portal">
    <!-- Navigation -->
<nav class="navbar">
      <div class="navbar-brand">
        <h1>🚗 Driver Portal</h1>
        <span v-if="driver" class="driver-status" :class="driver.status">
          {{ driver.status }}
        </span>
      </div>
      <div class="nav-items">
        <router-link to="/driver/tasks">Tasks</router-link>
        <router-link to="/driver/earnings">Earnings</router-link>
        <router-link to="/driver/profile">Profile</router-link>
        <button @click="logout">Logout</button>
      </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
      <div class="dashboard-grid">
        <!-- Stats Cards -->
        <div class="stats">
          <div class="stat-card">
            <h3>Today's Earnings</h3>
            <p class="amount">₱{{ todayEarnings }}</p>
          </div>
          <div class="stat-card">
            <h3>This Month</h3>
            <p class="amount">₱{{ monthEarnings }}</p>
          </div>
          <div class="stat-card">
            <h3>Rating</h3>
            <p class="rating">⭐ {{ driver?.average_rating }} / 5.0</p>
          </div>
          <div class="stat-card">
            <h3>Active Tasks</h3>
            <p class="count">{{ activeTasks.length }}</p>
          </div>
        </div>

        <!-- Active Tasks Section -->
        <div class="active-tasks">
          <h2>My Active Tasks</h2>
          <div v-if="activeTasks.length === 0" class="no-tasks">
            No active tasks right now
          </div>
          <div v-else class="tasks-list">
            <div v-for="task in activeTasks" :key="task.id" class="task-card">
              <div class="task-header">
                <h3>#{{ task.delivery.order.order_number }}</h3>
                <span class="status" :class="task.status">{{ task.status }}</span>
              </div>
              
              <div class="task-details">
                <p><strong>Customer:</strong> {{ task.delivery.order.customer_name }}</p>
                <p><strong>Address:</strong> {{ task.delivery.order.delivery_address }}</p>
                <p><strong>Amount:</strong> ₱{{ task.delivery.order.total_amount }}</p>
                <p v-if="task.delivery.order.special_instructions">
                  <strong>Notes:</strong> {{ task.delivery.order.special_instructions }}
                </p>
              </div>

              <div class="task-actions">
                <button @click="navigateTo(task)">📍 Navigate</button>
                <button @click="markArrived(task)">✓ Arrived</button>
                <button @click="showProofUpload(task)">📸 Upload Proof</button>
                <button @click="markComplete(task)" class="btn-primary">Complete</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Pending Tasks Section -->
        <div class="pending-tasks">
          <h2>Pending Task Assignments</h2>
          <div v-if="pendingTasks.length === 0" class="no-tasks">
            No pending tasks
          </div>
          <div v-else class="tasks-list">
            <div v-for="task in pendingTasks" :key="task.id" class="task-card pending">
              <div class="countdown" v-if="!task.responded_at">
                ⏰ Respond within 5 min
              </div>
              
              <h3>{{ task.delivery.order.customer_name }}</h3>
              <p><strong>Delivery Fee:</strong> ₱{{ driver.delivery_fee }}</p>
              <p><strong>Address:</strong> {{ task.delivery.order.delivery_address }}</p>

              <div class="task-actions">
                <button @click="acceptTask(task)" class="btn-accept">✓ Accept</button>
                <button @click="rejectTask(task)" class="btn-reject">✗ Reject</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'DriverPortal',
  data() {
    return {
      driver: null,
      activeTasks: [],
      pendingTasks: [],
      todayEarnings: 0,
      monthEarnings: 0,
      loading: false,
    };
  },
  methods: {
    async fetchDriver() {
      try {
        const response = await this.$api.get('/api/drivers/me');
        this.driver = response.data;
      } catch (error) {
        console.error('Error fetching driver:', error);
      }
    },
    async fetchTasks() {
      try {
        const active = await this.$api.get('/api/drivers/tasks/active');
        this.activeTasks = active.data;

        const pending = await this.$api.get('/api/drivers/tasks/pending');
        this.pendingTasks = pending.data;
      } catch (error) {
        console.error('Error fetching tasks:', error);
      }
    },
    async fetchEarnings() {
      try {
        const today = await this.$api.get('/api/drivers/earnings/today');
        this.todayEarnings = today.data.total;

        const month = await this.$api.get('/api/drivers/earnings/month');
        this.monthEarnings = month.data.total;
      } catch (error) {
        console.error('Error fetching earnings:', error);
      }
    },
    async acceptTask(task) {
      try {
        await this.$api.post(`/api/drivers/tasks/${task.id}/accept`);
        this.$notify.success('Task accepted');
        this.fetchTasks();
      } catch (error) {
        this.$notify.error(error.response?.data?.message || 'Failed to accept task');
      }
    },
    async rejectTask(task) {
      const reason = prompt('Why are you rejecting this task?');
      if (!reason) return;

      try {
        await this.$api.post(`/api/drivers/tasks/${task.id}/reject`, { reason });
        this.$notify.success('Task rejected');
        this.fetchTasks();
      } catch (error) {
        this.$notify.error('Failed to reject task');
      }
    },
    async markArrived(task) {
      // Get GPS coordinates
      navigator.geolocation.getCurrentPosition(
        async (position) => {
          try {
            await this.$api.post(`/api/drivers/tasks/${task.id}/arrived`, {
              gps_latitude: position.coords.latitude,
              gps_longitude: position.coords.longitude,
              gps_accuracy: position.coords.accuracy,
            });
            this.$notify.success('Marked as arrived');
            this.fetchTasks();
          } catch (error) {
            this.$notify.error('Failed to mark arrived');
          }
        }
      );
    },
    async markComplete(task) {
      try {
        await this.$api.post(`/api/drivers/tasks/${task.id}/complete`);
        this.$notify.success('Delivery completed! Earnings added.');
        this.fetchTasks();
        this.fetchEarnings();
      } catch (error) {
        this.$notify.error('Failed to complete delivery');
      }
    },
    navigateTo(task) {
      const address = task.delivery.order.delivery_address;
      const mapsUrl = `https://maps.google.com/?q=${encodeURIComponent(address)}`;
      window.open(mapsUrl, '_blank');
    },
    showProofUpload(task) {
      // Open modal for photo/signature upload
      this.$refs.proofModal.open(task);
    },
    logout() {
      this.$api.post('/api/drivers/auth/logout');
      this.$router.push('/');
    }
  },
  mounted() {
    this.fetchDriver();
    this.fetchTasks();
    this.fetchEarnings();

    // Refresh every 10 seconds
    setInterval(() => {
      this.fetchTasks();
      this.fetchEarnings();
    }, 10000);
  }
};
</script>

<style scoped>
.driver-portal {
  background: #f5f5f5;
  min-height: 100vh;
}

.navbar {
  background: white;
  padding: 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin: 2rem 0;
}

.stat-card {
  background: white;
  padding: 1.5rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-card h3 {
  font-size: 0.9rem;
  color: #666;
  margin-bottom: 0.5rem;
}

.stat-card .amount,
.stat-card .rating,
.stat-card .count {
  font-size: 2rem;
  font-weight: bold;
  color: #2c3e50;
}

.tasks-list {
  display: grid;
  gap: 1rem;
}

.task-card {
  background: white;
  padding: 1.5rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  border-left: 4px solid #2196F3;
}

.task-card.pending {
  border-left-color: #FF9800;
  position: relative;
}

.countdown {
  font-size: 0.9rem;
  color: #FF9800;
  margin-bottom: 0.5rem;
}

.task-actions {
  display: flex;
  gap: 0.5rem;
  margin-top: 1rem;
  flex-wrap: wrap;
}

.task-actions button {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 4px;
  background: #2196F3;
  color: white;
  cursor: pointer;
  font-size: 0.9rem;
}

.task-actions .btn-primary {
  background: #4CAF50;
}

.task-actions .btn-accept {
  background: #4CAF50;
}

.task-actions .btn-reject {
  background: #f44336;
}

.no-tasks {
  text-align: center;
  padding: 2rem;
  color: #999;
}
</style>
```

---

**Continue with remaining phases in next sections...**

This is the foundation. Run migrations, then proceed with notification system and real-time updates!
