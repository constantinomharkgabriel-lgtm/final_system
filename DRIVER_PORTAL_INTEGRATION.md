# 🔗 DRIVER PORTAL - INTEGRATION WITH EXISTING SYSTEM

## Key Changes to Existing Code

---

## 1️⃣ UPDATE DELIVERIES TABLE

Add driver assignment support:

```php
// database/migrations/[timestamp]_update_deliveries_table_add_driver.php

Schema::table('deliveries', function (Blueprint $table) {
    // NEW: Link delivery to driver directly (optional, via TaskAssignment mainly)
    $table->foreignId('driver_id')->nullable()->constrained('drivers')->onDelete('set null');
    
    // NEW: Track payment status separately
    $table->enum('payment_status', ['unpaid', 'pending', 'paid'])->default('unpaid');
    
    // NEW: Add these status options
    $table->enum('status', [
        'pending',
        'confirmed',
        'preparing',
        'packed',
        'ready_for_pickup',      // NEW
        'out_for_delivery',       // UPDATED scope
        'arrived',                // NEW (separate from out_for_delivery)
        'handed_over',            // NEW
        'awaiting_payment',       // NEW
        'payment_confirmed',      // NEW
        'delivered',
        'cancelled'
    ])->change();
});
```

---

## 2️⃣ UPDATE EMPLOYEES TABLE

Link employee to driver:

```php
// app/Models/Employee.php - Add to model

public function driver()
{
    return $this->hasOne(Driver::class);
}
```

When you create an employee with department="driver", automatically create a driver record:

```php
// app/Http/Controllers/EmployeeController.php

public function store(Request $request)
{
    $validated = $request->validate([
        // ... existing validation ...
    ]);

    $employee = Employee::create($validated);

    // Auto-assign driver role if department is driver
    if ($validated['department'] === 'driver' && !in_array('driver', $roles)) {
        $roles[] = 'driver';
    }

    // IF driver role assigned, create Driver profile
    if (in_array('driver', $roles)) {
        // Create User for driver authentication
        $driverUser = User::create([
            'name' => $employee->first_name . ' ' . $employee->last_name,
            'email' => $employee->email,
            'password' => Hash::make('temp_password_' . str_random(10)),
            'role' => 'driver',
            'status' => 'active',
        ]);

        // Generate driver code
        $driver_code = 'DRV' . date('Ymd') . str_pad($driverUser->id, 5, '0', STR_PAD_LEFT);

        // Create Driver profile
        Driver::create([
            'user_id' => $driverUser->id,
            'employee_id' => $employee->id,
            'driver_code' => $driver_code,
            'name' => $employee->first_name . ' ' . $employee->last_name,
            'email' => $employee->email,
            'phone' => $employee->phone,
            'vehicle_type' => $request->vehicle_type,
            'vehicle_plate' => $request->vehicle_plate,
            'vehicle_model' => $request->vehicle_model,
            'license_number' => $request->license_number,
            'license_expiry' => $request->license_expiry,
            'delivery_fee' => $request->delivery_fee,
            'status' => 'available',
            'is_verified' => false,
        ]);

        // Send welcome email to driver with login credentials
        Mail::send('emails.driver-welcome', [
            'email' => $employee->email,
            'driver_url' => 'https://yourdomain.com/driver/login',
        ], function ($message) use ($employee) {
            $message->to($employee->email)->subject('Driver Portal Access');
        });
    }

    return response()->json($employee, 201);
}
```

---

## 3️⃣ UPDATE LOGISTICS PORTAL

When creating a delivery, instead of assigning driver directly, create a TaskAssignment:

```php
// app/Http/Controllers/DeliveryController.php

public function create(Request $request)
{
    $validated = $request->validate([
        'order_id' => 'required|exists:orders,id',
        'farm_owner_id' => 'required|exists:farm_owners,id',
    ]);

    // Create delivery record
    $delivery = Delivery::create([
        'order_id' => $validated['order_id'],
        'farm_owner_id' => $validated['farm_owner_id'],
        'status' => 'ready_for_pickup',
        'payment_status' => 'unpaid',
    ]);

    // [OPTION 1] Auto-assign best available driver
    $bestDriver = Driver::verified()
        ->available()
        ->topRated()
        ->first();

    if ($bestDriver) {
        // Create task assignment (driver doesn't see it yet)
        $task = TaskAssignment::create([
            'delivery_id' => $delivery->id,
            'driver_id' => $bestDriver->id,
            'assigned_by_user_id' => auth()->id(),
            'status' => 'pending',
        ]);

        // Send notification to driver
        Notification::send($bestDriver->user, new NewDeliveryAssigned($task));
    }

    // [OPTION 2] Logistics manually selects driver
    // (Provide dropdown in UI with available drivers sorted by rating)

    return response()->json($delivery, 201);
}

/**
 * Get available drivers for assignment
 */
public function getAvailableDrivers()
{
    $drivers = Driver::verified()
        ->available()
        ->select('id', 'name', 'phone', 'vehicle_plate', 'average_rating', 'total_deliveries')
        ->orderByDesc('average_rating')
        ->get();

    return response()->json($drivers);
}
```

---

## 4️⃣ UPDATE CONSUMER NOTIFICATION SYSTEM

Send different notifications for different delivery statuses:

```php
// app/Notifications/DeliveryStatusUpdated.php

<?php

namespace App\Notifications;

use App\Models\Delivery;
use Illuminate\Notifications\Notification;

class DeliveryStatusUpdated extends Notification
{
    public function __construct(private Delivery $delivery)
    {}

    public function via($notifiable)
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail($notifiable)
    {
        $title = match($this->delivery->status) {
            'confirmed' => '✓ Order Confirmed!',
            'preparing' => '👨‍🍳 Being Prepared',
            'packed' => '📦 Order Packed',
            'ready_for_pickup' => '🚚 Waiting for Pickup',
            'out_for_delivery' => '🚗 Out for Delivery',
            'arrived' => '✓ Driver Arrived',
            'payment_confirmed' => '💰 Payment Received',
            'delivered' => '✅ Order Delivered',
            default => 'Order Status Updated'
        };

        return (new MailMessage)
            ->subject($title)
            ->line($title)
            ->when($this->delivery->status === 'out_for_delivery', function ($mail) {
                return $mail
                    ->line('Driver: ' . $this->delivery->driver->name)
                    ->line('Vehicle: ' . $this->delivery->driver->vehicle_plate)
                    ->line('Contact: ' . $this->delivery->driver->phone)
                    ->action('Track Order', route('orders.track', $this->delivery->order_id));
            })
            ->when($this->delivery->status === 'arrived', function ($mail) {
                return $mail
                    ->line('Your driver has arrived at your location!')
                    ->action('Confirm Receipt', route('orders.confirm', $this->delivery->order_id));
            })
            ->when($this->delivery->status === 'awaiting_payment', function ($mail) {
                return $mail
                    ->line('Amount: ₱' . $this->delivery->order->total_amount)
                    ->action('Confirm Payment', route('orders.payment', $this->delivery->order_id));
            });
    }

    public function toArray($notifiable)
    {
        return [
            'delivery_id' => $this->delivery->id,
            'order_id' => $this->delivery->order_id,
            'status' => $this->delivery->status,
            'driver_name' => $this->delivery->driver?->name,
            'driver_vehicle' => $this->delivery->driver?->vehicle_plate,
        ];
    }
}
```

Trigger notification on status change:

```php
// app/Models/Delivery.php

protected static function boot()
{
    parent::boot();

    static::updated(function ($delivery) {
        if ($delivery->wasChanged('status')) {
            // Send to consumer
            $delivery->order->consumer->notify(
                new DeliveryStatusUpdated($delivery)
            );

            // Send to logistics staff
            if (in_array($delivery->status, ['arrived', 'payment_confirmed', 'delivered'])) {
                Notification::send(LogisticsStaff::all(), new DeliveryProofReceived($delivery));
            }
        }
    });
}
```

---

## 5️⃣ UPDATE CONSUMER WEB/MOBILE APP

### Consumer Web Marketplace - Order Tracking

```vue
<!-- resources/js/pages/OrderTracking.vue -->

<template>
  <div class="order-tracking">
    <div class="status-timeline">
      <div v-for="status in statusSteps" :key="status.key" class="step" :class="{ complete: isStatusComplete(status.key) }">
        <div class="step-icon">{{ status.icon }}</div>
        <div class="step-label">{{ status.label }}</div>
      </div>
    </div>

    <div v-if="delivery.status === 'out_for_delivery'" class="driver-section">
      <h3>🚗 Your Driver</h3>
      <div class="driver-card">
        <img :src="delivery.driver.avatar" :alt="delivery.driver.name" class="driver-avatar">
        <div class="driver-info">
          <p><strong>{{ delivery.driver.name }}</strong></p>
          <p class="rating">⭐ {{ delivery.driver.average_rating }} / 5.0</p>
          <p class="vehicle">{{ delivery.driver.vehicle_plate }}</p>
          <div class="actions">
            <button @click="callDriver">📞 Call</button>
            <button @click="messageDriver">💬 Message</button>
          </div>
        </div>
      </div>

      <div class="map-container" v-if="driverLocation">
        <div id="delivery-map"></div>
        <p class="est-arrival">Estimated arrival: {{ estimatedArrival }}</p>
      </div>
    </div>

    <div v-if="delivery.status === 'arrived'" class="ready-payment">
      <h3>💵 Ready to Pay?</h3>
      <div class="payment-info">
        <p>Amount: <strong>₱{{ order.total_amount }}</strong></p>
        <p>Payment Method: {{ delivery.payment_method }}</p>
      </div>
      <div class="actions">
        <button @click="confirmPayment" class="btn-primary">✓ Confirm Payment Received</button>
        <button @click="waitingPayment">⏰ Still Waiting</button>
      </div>
    </div>

    <div v-if="delivery.status === 'delivered'" class="post-delivery">
      <h3>✅ Order Delivered!</h3>
      <button @click="showRatingModal" class="btn-primary">⭐ Rate Order & Driver</button>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      delivery: null,
      order: null,
      driverLocation: null,
      statusSteps: [
        { key: 'placed', label: 'Order Placed', icon: '📝' },
        { key: 'confirmed', label: 'Confirmed', icon: '✓' },
        { key: 'preparing', label: 'Preparing', icon: '👨‍🍳' },
        { key: 'packed', label: 'Packed', icon: '📦' },
        { key: 'out_for_delivery', label: 'Out for Delivery', icon: '🚗' },
        { key: 'arrived', label: 'Arrived', icon: '🏠' },
        { key: 'delivered', label: 'Delivered', icon: '✅' },
      ],
    };
  },
  methods: {
    async fetchDelivery() {
      const response = await this.$api.get(`/api/deliveries/${this.$route.params.id}`);
      this.delivery = response.data.delivery;
      this.order = response.data.order;
    },
    async confirmPayment() {
      await this.$api.post(`/api/deliveries/${this.delivery.id}/payment/confirm`);
      await this.fetchDelivery();
      this.$notify.success('Payment confirmed');
    },
    isStatusComplete(status) {
      const statuses = ['placed', 'confirmed', 'preparing', 'packed', 'out_for_delivery', 'arrived', 'delivered'];
      const currentIndex = statuses.indexOf(this.delivery.status);
      const stepIndex = statuses.indexOf(status);
      return stepIndex <= currentIndex;
    },
    callDriver() {
      window.location.href = `tel:${this.delivery.driver.phone}`;
    },
    messageDriver() {
      // Open messaging interface
      this.$refs.messageModal.open(this.delivery.driver);
    },
  },
  mounted() {
    this.fetchDelivery();

    // Refresh every 5 seconds
    this.refreshInterval = setInterval(() => {
      this.fetchDelivery();
    }, 5000);
  },
  beforeUnmount() {
    clearInterval(this.refreshInterval);
  }
};
</script>
```

### Flutter Mobile App - Live Tracking

```dart
// lib/screens/order_tracking_screen.dart

class OrderTrackingScreen extends StatefulWidget {
  final String deliveryId;

  const OrderTrackingScreen({required this.deliveryId});

  @override
  _OrderTrackingScreenState createState() => _OrderTrackingScreenState();
}

class _OrderTrackingScreenState extends State<OrderTrackingScreen> {
  late StreamSubscription<Delivery> _deliverySubscription;
  Delivery? delivery;
  GoogleMapController? _mapController;

  @override
  initState() {
    super.initState();
    _subscribeToDeliveryUpdates();
  }

  void _subscribeToDeliveryUpdates() {
    _deliverySubscription = _deliveryService
        .getDeliveryStream(widget.deliveryId)
        .listen((updatedDelivery) {
      setState(() {
        delivery = updatedDelivery;
      });

      if (delivery?.status == 'out_for_delivery') {
        _updateMapMarkers();
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    if (delivery == null) return Scaffold(body: LoadingWidget());

    final statusSteps = [
      ('📝', 'Placed', delivery!.createdAt),
      ('✓', 'Confirmed', delivery!.confirmedAt),
      ('👨‍🍳', 'Preparing', delivery!.preparingAt),
      ('📦', 'Packed', delivery!.packedAt),
      ('🚗', 'Out for Delivery', delivery!.outForDeliveryAt),
      ('🏠', 'Arrived', delivery!.arrivedAt),
      ('✅', 'Delivered', delivery!.deliveredAt),
    ];

    return Scaffold(
      appBar: AppBar(title: Text('Order #${delivery!.orderId}')),
      body: SingleChildScrollView(
        child: Column(
          children: [
            // Status Timeline
            Padding(
              padding: EdgeInsets.all(16),
              child: StatusTimeline(steps: statusSteps),
            ),

            // Driver Info (if en route)
            if (delivery!.status == 'out_for_delivery') ...[
              DriverCard(driver: delivery!.driver!),
              SizedBox(height: 16),
            ],

            // Live Map
            if (delivery!.status == 'out_for_delivery')
              Container(
                height: 300,
                child: GoogleMap(
                  onMapCreated: (GoogleMapController controller) {
                    _mapController = controller;
                  },
                  initialCameraPosition: CameraPosition(
                    target: LatLng(
                      delivery!.driver!.latitude,
                      delivery!.driver!.longitude,
                    ),
                    zoom: 15,
                  ),
                  markers: _buildMarkers(),
                  polylines: _buildPolylines(),
                ),
              ),

            // Payment Confirmation
            if (delivery!.status == 'arrived')
              PaymentConfirmationWidget(delivery: delivery!),

            // Rating Section
            if (delivery!.status == 'delivered')
              RatingWidget(orderId: delivery!.orderId),
          ],
        ),
      ),
    );
  }

  Set<Marker> _buildMarkers() {
    final markers = <Marker>{};

    // Destination
    markers.add(
      Marker(
        markerId: MarkerId('destination'),
        position: LatLng(
          delivery!.destinationLatitude,
          delivery!.destinationLongitude,
        ),
        icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueBlue),
      ),
    );

    // Driver
    if (delivery!.driver != null) {
      markers.add(
        Marker(
          markerId: MarkerId('driver'),
          position: LatLng(
            delivery!.driver!.latitude,
            delivery!.driver!.longitude,
          ),
          icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueGreen),
          infoWindow: InfoWindow(
            title: delivery!.driver!.name,
            snippet: delivery!.driver!.vehiclePlate,
          ),
        ),
      );
    }

    return markers;
  }

  @override
  void dispose() {
    _deliverySubscription.cancel();
    _mapController?.dispose();
    super.dispose();
  }
}
```

---

## 6️⃣ LOGISTICS PORTAL UPDATES

Update delivery creation flow:

```php
// resources/views/logistics/deliveries/create.blade.php

<form action="{{ route('logistics.deliveries.store') }}" method="POST">
    @csrf

    <div class="form-group">
        <label for="order_id">Order</label>
        <select name="order_id" class="form-control" onchange="loadOrder(this.value)" required>
            <option value="">Select Order</option>
            @foreach($orders as $order)
                <option value="{{ $order->id }}">
                    #{{ $order->order_number }} - {{ $order->customer_name }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- Auto-assigned driver info --}}
    <div class="best-driver-suggestion">
        <h4>🏆 Recommended Driver</h4>
        <div class="driver-info" id="best-driver">
            <p>Loading...</p>
        </div>
    </div>

    {{-- Manual driver selection --}}
    <div class="form-group">
        <label for="driver_id">Or Select Driver Manually</label>
        <select name="driver_id" class="form-control" id="driver_id">
            <option value="">Auto-assign to best available</option>
        </select>
    </div>

    <button type="submit" class="btn btn-primary">Create Delivery & Assign</button>
</form>

<script>
async function loadOrder(orderId) {
    const response = await fetch(`/api/logistics/orders/${orderId}`);
    const data = await response.json();

    // Get best driver for this location
    const drivers = await fetch('/api/logistics/drivers/available').then(r => r.json());
    
    document.getElementById('best-driver').innerHTML = `
        <div class="driver-card">
            <strong>${drivers[0].name}</strong>
            <p>Rating: ⭐ ${drivers[0].average_rating}</p>
            <p>Vehicle: ${drivers[0].vehicle_plate}</p>
            <button type="button" onclick="selectDriver(${drivers[0].id})">Use This Driver</button>
        </div>
    `;

    // Populate driver dropdown
    const select = document.getElementById('driver_id');
    select.innerHTML = '<option value="">Auto-assign</option>';
    drivers.forEach(driver => {
        const option = document.createElement('option');
        option.value = driver.id;
        option.textContent = `${driver.name} (${driver.average_rating}⭐)`;
        select.appendChild(option);
    });
}

function selectDriver(driverId) {
    document.getElementById('driver_id').value = driverId;
}
</script>
```

---

## 7️⃣ ADMIN VERIFICATION

New driver verification system:

```php
// app/Http/Controllers/Admin/DriverVerificationController.php

public function pending()
{
    $drivers = Driver::where('is_verified', false)
        ->with('user')
        ->orderByDesc('created_at')
        ->paginate(20);

    return view('admin.drivers.pending', ['drivers' => $drivers]);
}

public function approve(Driver $driver)
{
    $driver->update(['is_verified' => true]);

    // Send approval email
    Mail::send('emails.driver-approved', ['driver' => $driver], 
        function ($m) use ($driver) {
            $m->to($driver->email)->subject('Your Driver Account is Verified!');
        }
    );

    return back()->with('success', 'Driver approved');
}

public function reject(Request $request, Driver $driver)
{
    $validated = $request->validate([
        'rejection_reason' => 'required|string',
    ]);

    $driver->delete();

    Mail::send('emails.driver-rejected', $validated, 
        function ($m) use ($driver) {
            $m->to($driver->email)->subject('Driver Account Application - Review Required');
        }
    );

    return back()->with('success', 'Driver rejected');
}
```

---

## 8️⃣ REAL-TIME NOTIFICATIONS (Optional - Using Pusher/Laravel Echo)

```php
// Broadcast event when delivery status changes

Event::listen(\App\Events\DeliveryStatusChanged::class, function ($event) {
    broadcast(new DeliveryStatusChanged($event->delivery))
        ->toOthers();
});
```

---

## IMPLEMENTATION CHECKLIST

```
Phase 1: Database & Models
☐ Create Driver model
☐ Create TaskAssignment model
☐ Create DeliveryProof model
☐ Create PaymentConfirmation model
☐ Create DriverEarning model
☐ Run migrations
☐ Seed driver role

Phase 2: Authentication
☐ Create DriverAuthController
☐ Implement driver registration
☐ Implement driver login
☐ Add Sanctum middleware

Phase 3: Task Management API
☐ Create DriverTaskController
☐ Implement accept/reject workflow
☐ Implement proof upload
☐ Setup real-time notifications

Phase 4: Logistics Integration
☐ Update DeliveryController (create TaskAssignment)
☐ Update available drivers endpoint
☐ Add driver auto-assignment logic
☐ Update Logistics UI

Phase 5: Consumer Notifications
☐ Update notification system for status changes
☐ Send driver info notifications
☐ Implement payment confirmations
☐ Add real-time tracking

Phase 6: Frontend - Driver Portal
☐ Build Driver dashboard
☐ Implement task acceptance/rejection UI
☐ Build GPS tracking
☐ Implement proof upload UI
☐ Build earnings dashboard

Phase 7: Frontend - Consumer App
☐ Update Flutter for live tracking
☐ Add driver info display
☐ Implement real-time map updates
☐ Add payment confirmation UI

Phase 8: Testing & Deployment
☐ End-to-end testing
☐ Load testing
☐ Security audit
☐ Production deployment
```

---

**Start with Phase 1 (database & models), then proceed sequentially!** 🚀
