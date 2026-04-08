<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\FarmOwnerController;
use App\Http\Controllers\FarmOwnerAuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\ConsumerRegistrationController;
use App\Http\Controllers\ConsumerVerificationController;
use App\Http\Controllers\ClientRequestController;
use App\Http\Controllers\EggController;
use App\Http\Controllers\ChickenController;
use App\Http\Controllers\FlockController;
use App\Http\Controllers\VaccinationController;
use App\Http\Controllers\SupplyController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\DepartmentUserController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\InternalCommunicationController;
use App\Http\Controllers\ConsumerPortalController;
use App\Http\Controllers\DriverPortalController;
use App\Http\Controllers\DriverAuthController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InventoryTestController;
use App\Http\Controllers\SubscriptionTestController;
use App\Http\Controllers\SubscriptionDiagnosticsController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| 1. Public / Guest Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('welcome');
});

// Guest Routes - Open to Guests Only
Route::middleware('guest')->group(function () {
    // Farm Owner Authentication & Registration
    Route::get('/farm-owner/login', [FarmOwnerAuthController::class, 'show_login'])->name('farmowner.login');
    Route::post('/farm-owner/login', [FarmOwnerAuthController::class, 'login'])->name('farmowner.login.store');
    Route::get('/farm-owner/register', [FarmOwnerAuthController::class, 'show_register'])->name('farmowner.register');
    Route::post('/farm-owner/register', [FarmOwnerAuthController::class, 'register'])->name('farmowner.register.store');

    // Consumer & Client Registration
    Route::get('/register', function () {
        return view('auth.register-select');
    })->name('register');

    Route::get('/client/register', function () {
        return view('auth.client-register');
    })->name('client.register');

    Route::get('/consumer/register', function () {
        return view('auth.consumer-register');
    })->name('consumer.register');

    Route::post('/client/register', [ClientRequestController::class, 'store'])->name('client.request.store');
    Route::post('/consumer/register', [ConsumerRegistrationController::class, 'store'])->name('consumer.store');

    // Consumer Email Verification
    Route::get('/consumer/verify', [ConsumerVerificationController::class, 'show'])
        ->name('consumer.verify.form');
    Route::post('/consumer/verify', [ConsumerVerificationController::class, 'verify'])
        ->name('consumer.verify.submit');
    Route::post('/consumer/verify/resend', [ConsumerVerificationController::class, 'resend'])
        ->name('consumer.verify.resend');

    // Driver Email Verification
    Route::get('/driver/verify/{driver}/{hash}', [\App\Http\Controllers\DriverVerificationController::class, 'verify'])
        ->name('driver.email.verify');

    // Driver Portal Login (for unverified drivers)
    Route::get('/driver/login', function () {
        return view('driver.auth.login');
    })->name('driver.login');
    Route::post('/driver/login', [\App\Http\Controllers\DriverAuthController::class, 'login'])->name('driver.login.submit');

    // Driver Email Verification Pending Page
    Route::get('/driver/verification-pending', function () {
        return view('driver.auth.verification-pending');
    })->name('driver.verification.pending');

    Route::get('/consumer/go-to-app', function () {
        return view('auth.consumer-go-to-app');
    })->name('consumer.app.launch');
});

// PayMongo Webhook (no auth, no CSRF)
Route::post('/webhooks/paymongo', [SubscriptionController::class, 'handleWebhook'])->name('webhooks.paymongo');

/*
|--------------------------------------------------------------------------
| 2. Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    
    // 1. THIS IS THE ONLY DASHBOARD ROUTE YOU NEED
    Route::get('/dashboard', function () {
        $user = Auth::user();
        
        if ($user->isSuperAdmin()) {
            // This sends you to the correct orange/black portal
            return redirect()->route('superadmin.dashboard');
        }

        if ($user->role === 'client') {
            return redirect()->route('client.dashboard');
        }

        if ($user->isFarmOwner()) {
            return redirect()->route('farmowner.dashboard');
        }

        if ($user->role === 'driver') {
            // Check if driver is verified
            $driver = $user->driver;
            if ($driver && !$driver->is_verified) {
                return redirect()->route('driver.verification.pending')
                    ->with('info', 'Please verify your email first to access the driver portal.');
            }
            return redirect()->route('driver.dashboard');
        }

        if ($user->isHR()) {
            return redirect()->route('hr.users.index');
        }

        if ($user->isDepartmentRole()) {
            $routeName = $user->departmentDashboardRouteName();

            if ($routeName) {
                return redirect()->route($routeName);
            }

            return view('dashboard');
        }

        // Fallback for others (like consumers)
        return view('dashboard'); 
    })->name('dashboard');

    // --- Driver Portal (Verified Drivers Only) ---
    Route::middleware(['auth', 'role:driver'])->prefix('driver')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\DriverPortalController::class, 'dashboard'])->name('driver.dashboard');
        Route::get('/profile', [\App\Http\Controllers\DriverPortalController::class, 'profile'])->name('driver.profile');
        Route::get('/deliveries', [\App\Http\Controllers\DriverPortalController::class, 'deliveries'])->name('driver.deliveries');
        Route::get('/deliveries/{delivery}', [\App\Http\Controllers\DriverPortalController::class, 'showDelivery'])->name('driver.deliveries.show');
        Route::post('/deliveries/{delivery}/accept', [\App\Http\Controllers\DriverPortalController::class, 'acceptDelivery'])->name('driver.deliveries.accept');
        Route::post('/deliveries/{delivery}/reject', [\App\Http\Controllers\DriverPortalController::class, 'rejectDelivery'])->name('driver.deliveries.reject');
        Route::post('/deliveries/{delivery}/start', [\App\Http\Controllers\DriverPortalController::class, 'startDelivery'])->name('driver.deliveries.start');
        Route::post('/deliveries/{delivery}/complete', [\App\Http\Controllers\DriverPortalController::class, 'completeDelivery'])->name('driver.deliveries.complete');
        Route::get('/earnings', [\App\Http\Controllers\DriverPortalController::class, 'earnings'])->name('driver.earnings');
        Route::post('/logout', [\App\Http\Controllers\DriverAuthController::class, 'logout'])->name('driver.logout');
    });

    // --- Super Admin Portal ---
    Route::middleware(['auth', 'role:superadmin'])->group(function () {
        Route::get('/super-admin/dashboard', [SuperAdminController::class, 'index'])->name('superadmin.dashboard');
        Route::get('/super-admin/farm-owners', [SuperAdminController::class, 'farm_owners'])->name('superadmin.farm_owners');
        Route::get('/super-admin/farm-owners/{id}', [SuperAdminController::class, 'show_farm_owner'])->name('superadmin.show_farm_owner');
        Route::post('/super-admin/farm-owners/{id}/approve', [SuperAdminController::class, 'approve_farm_owner'])->name('superadmin.approve_farm_owner');
        Route::post('/super-admin/farm-owners/{id}/reject', [SuperAdminController::class, 'reject_farm_owner'])->name('superadmin.reject_farm_owner');
        Route::get('/super-admin/orders', [SuperAdminController::class, 'orders'])->name('superadmin.orders');
        Route::get('/super-admin/monitoring', [SuperAdminController::class, 'monitoring'])->name('superadmin.monitoring');
        Route::get('/super-admin/subscriptions', [SuperAdminController::class, 'subscriptions'])->name('superadmin.subscriptions');
        Route::get('/super-admin/users', [SuperAdminController::class, 'users'])->name('superadmin.users');
        Route::get('/super-admin/support', [SupportController::class, 'adminIndex'])->name('superadmin.support.index');
        Route::get('/super-admin/support/{ticket}', [SupportController::class, 'adminShow'])->name('superadmin.support.show');
        Route::post('/super-admin/support/{ticket}/reply', [SupportController::class, 'adminReply'])->name('superadmin.support.reply');
        Route::post('/super-admin/support/{ticket}/close', [SupportController::class, 'adminClose'])->name('superadmin.support.close');
    });

    // --- HR & Department User Management ---
    Route::middleware(['auth', 'role:superadmin,hr'])->group(function () {
        Route::get('/hr/users', [DepartmentUserController::class, 'index'])->name('hr.users.index');
        Route::get('/hr/users/create', [DepartmentUserController::class, 'create'])->name('hr.users.create');
        Route::post('/hr/users', [DepartmentUserController::class, 'store'])->name('hr.users.store');
    });

    Route::middleware(['auth', 'role:farm_operations'])->group(function () {
        Route::get('/department/farm-operations', [DepartmentController::class, 'farmOperations'])->name('department.farm_operations.dashboard');
    });

    Route::middleware(['auth', 'role:finance'])->group(function () {
        Route::get('/department/finance', [DepartmentController::class, 'finance'])->name('department.finance.dashboard');
    });

    Route::middleware(['auth', 'role:logistics'])->group(function () {
        Route::get('/department/logistics', [DepartmentController::class, 'logistics'])->name('department.logistics.dashboard');
        
        // Logistics - Drivers & Deliveries (dedicated portal)
        Route::resource('department/logistics/drivers', DriverController::class)
            ->parameter('drivers', 'driver')
            ->names([
                'index' => 'logistics.drivers.index',
                'create' => 'logistics.drivers.create',
                'store' => 'logistics.drivers.store',
                'show' => 'logistics.drivers.show',
                'edit' => 'logistics.drivers.edit',
                'update' => 'logistics.drivers.update',
                'destroy' => 'logistics.drivers.destroy',
            ]);
        Route::resource('department/logistics/deliveries', DeliveryController::class)
            ->parameter('deliveries', 'delivery')
            ->names([
                'index' => 'logistics.deliveries.index',
                'create' => 'logistics.deliveries.create',
                'store' => 'logistics.deliveries.store',
                'show' => 'logistics.deliveries.show',
                'edit' => 'logistics.deliveries.edit',
                'update' => 'logistics.deliveries.update',
                'destroy' => 'logistics.deliveries.destroy',
            ]);
        Route::post('department/logistics/deliveries/{delivery}/assign-driver', [DeliveryController::class, 'assignDriver'])->name('logistics.deliveries.assignDriver');
        Route::post('department/logistics/deliveries/{delivery}/mark-packed', [DeliveryController::class, 'markPacked'])->name('logistics.deliveries.markPacked');
        Route::post('department/logistics/deliveries/{delivery}/dispatch', [DeliveryController::class, 'dispatch'])->name('logistics.deliveries.dispatch');
        Route::post('department/logistics/deliveries/{delivery}/mark-delivered', [DeliveryController::class, 'markDelivered'])->name('logistics.deliveries.markDelivered');
        Route::post('department/logistics/deliveries/{delivery}/mark-completed', [DeliveryController::class, 'markCompleted'])->name('logistics.deliveries.markCompleted');
        Route::post('department/logistics/deliveries/{delivery}/mark-failed', [DeliveryController::class, 'markFailed'])->name('logistics.deliveries.markFailed');
        Route::get('department/logistics/delivery-schedule', [DeliveryController::class, 'schedule'])->name('logistics.deliveries.schedule');
    });

    Route::middleware(['auth', 'role:sales'])->group(function () {
        Route::get('/department/sales', [DepartmentController::class, 'sales'])->name('department.sales.dashboard');
    });

    Route::middleware(['auth', 'role:admin'])->group(function () {
        Route::get('/department/admin', [DepartmentController::class, 'admin'])->name('department.admin.dashboard');
    });

    Route::middleware(['auth', 'role:hr'])->group(function () {
        Route::get('/department/hr', [DepartmentController::class, 'hr'])->name('department.hr.dashboard');
    });

    Route::middleware(['auth', 'role:farm_owner'])->prefix('farm-owner')->group(function () {
        Route::get('/pending', function () {
            $farmOwner = Auth::user()?->farmOwner;
            return view('farmowner.pending-approval', compact('farmOwner'));
        })->name('farmowner.pending');
        // Logout route - accessible regardless of approval/subscription status
        Route::post('/logout', [FarmOwnerAuthController::class, 'logout'])->name('farmowner.logout');
    });
    
    // --- Farm Owner Routes (Authenticated) ---
    Route::middleware(['auth', 'role:farm_owner', 'permit.approved', 'subscription.active'])->prefix('farm-owner')->group(function () {
        // Dashboard & Profile
        Route::get('/dashboard', [FarmOwnerController::class, 'dashboard'])->name('farmowner.dashboard');
        Route::get('/profile', [FarmOwnerController::class, 'profile'])->name('farmowner.profile');
        Route::put('/profile', [FarmOwnerController::class, 'update_profile'])->name('farmowner.update_profile');
        Route::get('/subscriptions', [FarmOwnerController::class, 'subscriptions'])->name('farmowner.subscriptions');

        // Support
        Route::get('/support', [SupportController::class, 'farmOwnerIndex'])->name('farmowner.support.index');
        Route::post('/support', [SupportController::class, 'farmOwnerStore'])->name('farmowner.support.store');
        Route::get('/support/{ticket}', [SupportController::class, 'farmOwnerShow'])->name('farmowner.support.show');
        Route::post('/support/{ticket}/reply', [SupportController::class, 'farmOwnerReply'])->name('farmowner.support.reply');
        Route::get('/notifications', [SupportController::class, 'farmOwnerNotifications'])->name('farmowner.notifications.index');
        Route::post('/notifications/read-all', [SupportController::class, 'farmOwnerMarkNotificationsRead'])->name('farmowner.notifications.readAll');
    });

    // --- Workforce Modules (Farm Owner + HR + Admin) ---
    Route::middleware(['auth', 'role:farm_owner,hr,admin', 'permit.approved', 'subscription.active'])->prefix('farm-owner')->group(function () {
        // HR - Employees
        Route::resource('employees', EmployeeController::class)
            ->except(['destroy']);
        Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');

        // HR - Attendance
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
        Route::post('/attendance/bulk', [AttendanceController::class, 'bulkStore'])->name('attendance.bulk');
        Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clockIn');
        Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clockOut');
        Route::get('/attendance/report', [AttendanceController::class, 'report'])->name('attendance.report');
    });

    // Payroll - HR prepares, Finance reviews/release, Farm Owner finalizes and approves manual edits.
    Route::middleware(['auth', 'role:farm_owner,hr,finance', 'permit.approved', 'subscription.active'])->prefix('farm-owner')->group(function () {
        Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::get('/payroll/create', [PayrollController::class, 'create'])
            ->middleware('role:hr')
            ->name('payroll.create');
        Route::post('/payroll', [PayrollController::class, 'store'])
            ->middleware('role:hr')
            ->name('payroll.store');
        Route::get('/payroll/{payroll}', [PayrollController::class, 'show'])->name('payroll.show');
        Route::delete('/payroll/{payroll}', [PayrollController::class, 'destroy'])
            ->middleware('role:hr')
            ->name('payroll.destroy');
        Route::post('/payroll/generate-batch', [PayrollController::class, 'generateBatch'])
            ->middleware('role:hr')
            ->name('payroll.generateBatch');
        Route::post('/payroll/{payroll}/finance-approve', [PayrollController::class, 'financeApprove'])
            ->middleware('role:finance')
            ->name('payroll.financeApprove');
        Route::post('/payroll/{payroll}/release-payslip', [PayrollController::class, 'releasePayslip'])
            ->middleware('role:finance')
            ->name('payroll.releasePayslip');
        Route::post('/payroll/{payroll}/request-edit', [PayrollController::class, 'requestEdit'])
            ->middleware('role:hr')
            ->name('payroll.requestEdit');
        Route::post('/payroll/edit-requests/{editRequest}/approve', [PayrollController::class, 'approveEditRequest'])
            ->middleware('role:farm_owner')
            ->name('payroll.editRequests.approve');
        Route::post('/payroll/edit-requests/{editRequest}/reject', [PayrollController::class, 'rejectEditRequest'])
            ->middleware('role:farm_owner')
            ->name('payroll.editRequests.reject');
        Route::post('/payroll/{payroll}/approve', [PayrollController::class, 'approve'])
            ->middleware('role:farm_owner')
            ->name('payroll.approve');
        Route::post('/payroll/{payroll}/mark-paid', [PayrollController::class, 'markPaid'])
            ->middleware('role:farm_owner')
            ->name('payroll.markPaid');
        Route::post('/payroll/{payroll}/prepare-disbursement', [PayrollController::class, 'prepareDisbursement'])
            ->middleware('role:farm_owner')
            ->name('payroll.prepareDisbursement');
        Route::post('/payroll/{payroll}/execute-disbursement', [PayrollController::class, 'executeDisbursement'])
            ->middleware('role:finance')
            ->name('payroll.executeDisbursement');
        Route::get('/payroll/{payroll}/paymongo-success', [PayrollController::class, 'handlePayMongoSuccess'])
            ->middleware('role:finance')
            ->name('payroll.paymongo-success');
    });

    // --- Shared Farm Modules (Farm Owner + Department Employees) ---
    Route::middleware(['auth', 'role:farm_owner,farm_operations,finance,sales,admin,logistics', 'permit.approved', 'subscription.active'])->prefix('farm-owner')->group(function () {
        Route::resource('flocks', FlockController::class);
        Route::post('/flocks/{flock}/record', [FlockController::class, 'addRecord'])->name('flocks.record');

        // Vaccination & Health
        Route::resource('vaccinations', VaccinationController::class);
        Route::get('/vaccinations-upcoming', [VaccinationController::class, 'upcoming'])->name('vaccinations.upcoming');

        // Supply/Inventory Management
        Route::resource('supplies', SupplyController::class);
        Route::post('/supplies/{supply}/stock-in', [SupplyController::class, 'stockIn'])->name('supplies.stockIn');
        Route::post('/supplies/{supply}/stock-out', [SupplyController::class, 'stockOut'])->name('supplies.stockOut');
        Route::get('/supplies-alerts', [SupplyController::class, 'lowStock'])->name('supplies.alerts');

        // INVENTORY SYSTEM (For Products FOR SALE - Auto-created from daily records, vaccinations, etc)
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('/inventory/{type}/{id}', [InventoryController::class, 'show'])->name('inventory.show');
        Route::get('/inventory/available-for-product', [InventoryController::class, 'getAvailableForProduct'])->name('inventory.available');
        Route::get('/inventory/alerts', [InventoryController::class, 'getLowStockAlerts'])->name('inventory.alerts');
        Route::get('/inventory/export', [InventoryController::class, 'export'])->name('inventory.export');

        // Supplier Management
        Route::resource('suppliers', SupplierController::class);

        // Finance - Expenses
        Route::resource('expenses', ExpenseController::class);
        Route::post('/expenses/{expense}/mark-paid', [ExpenseController::class, 'markPaid'])->name('expenses.markPaid');

        // Finance - Income
        Route::resource('income', IncomeController::class);

        // Logistics - Drivers & Deliveries
        Route::resource('drivers', DriverController::class);
        
        // Driver Verification Admin Routes
        Route::post('/drivers/{driver}/approve-verification', [\App\Http\Controllers\DriverVerificationAdminController::class, 'approveDriver'])->name('drivers.approveVerification');
        Route::post('/drivers/{driver}/reject-verification', [\App\Http\Controllers\DriverVerificationAdminController::class, 'rejectDriver'])->name('drivers.rejectVerification');
        Route::get('/drivers-pending-verification', [\App\Http\Controllers\DriverVerificationAdminController::class, 'pendingDrivers'])->name('drivers.pendingVerification');
        
        Route::resource('deliveries', DeliveryController::class);
        Route::post('/deliveries/{delivery}/assign-driver', [DeliveryController::class, 'assignDriver'])->name('deliveries.assignDriver');
        Route::post('/deliveries/{delivery}/mark-packed', [DeliveryController::class, 'markPacked'])->name('deliveries.markPacked');
        Route::post('/deliveries/{delivery}/dispatch', [DeliveryController::class, 'dispatch'])->name('deliveries.dispatch');
        Route::post('/deliveries/{delivery}/mark-delivered', [DeliveryController::class, 'markDelivered'])->name('deliveries.markDelivered');
        Route::post('/deliveries/{delivery}/mark-completed', [DeliveryController::class, 'markCompleted'])->name('deliveries.markCompleted');
        Route::post('/deliveries/{delivery}/mark-failed', [DeliveryController::class, 'markFailed'])->name('deliveries.markFailed');
        Route::get('/delivery-schedule', [DeliveryController::class, 'schedule'])->name('deliveries.schedule');

        // Reports & Analytics (DSS)
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/dashboard', [ReportController::class, 'dashboard'])->name('reports.dashboard');
        Route::get('/reports/financial', [ReportController::class, 'financial'])->name('reports.financial');
        Route::get('/reports/financial/series', [ReportController::class, 'financialSeries'])->name('reports.financial.series');
        Route::get('/reports/production', [ReportController::class, 'production'])->name('reports.production');
        Route::get('/reports/inventory', [ReportController::class, 'inventory'])->name('reports.inventory');
        Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
        Route::get('/reports/delivery', [ReportController::class, 'delivery'])->name('reports.delivery');
        Route::get('/reports/payroll', [ReportController::class, 'payroll'])->name('reports.payroll');
        Route::get('/reports/export/{type}', [ReportController::class, 'exportCsv'])->name('reports.export');
    });

    // Internal communication: farm owner to HR/Finance, and department coordination.
    Route::middleware(['role:farm_owner', 'permit.approved', 'subscription.active'])->prefix('farm-owner')->group(function () {
        Route::get('/contact/hr', [InternalCommunicationController::class, 'contactHr'])
            ->name('farmowner.contact.hr');
        Route::get('/contact/finance', [InternalCommunicationController::class, 'contactFinance'])
            ->name('farmowner.contact.finance');
    });

    // Communication routes for farm owners and department users (HR, Finance)
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::post('/communication', [InternalCommunicationController::class, 'store'])->name('communication.store');
    });

    Route::middleware(['role:hr,finance'])->group(function () {
        Route::get('/department/messages', [InternalCommunicationController::class, 'departmentInbox'])->name('department.messages');
    });

    // --- Product Routes ---
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/farm-owner/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/farm-owner/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/farm-owner/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/farm-owner/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/farm-owner/products/{product}', [ProductController::class, 'delete'])->name('products.delete');
    Route::patch('/farm-owner/products/{product}/stock', [ProductController::class, 'update_stock'])->name('products.update-stock');

    // --- Order Routes ---
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/cart/add', [OrderController::class, 'cart_add'])->name('cart.add');
    Route::post('/cart/update', [OrderController::class, 'cart_update'])->name('cart.update');
    Route::post('/cart/remove', [OrderController::class, 'cart_remove'])->name('cart.remove');
    Route::get('/checkout', [OrderController::class, 'checkout'])->name('checkout');
    Route::post('/orders', [OrderController::class, 'place_order'])->name('orders.place');
    Route::get('/orders/{order}/verify-payment', [OrderController::class, 'verifyPaymentStatus'])->name('orders.verify-payment');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel_order'])->name('orders.cancel');
    Route::post('/orders/{order}/retry-payment', [OrderController::class, 'retry_payment'])->name('orders.retry-payment');
    Route::post('/farm-owner/orders/{order}/confirm', [OrderController::class, 'confirm_order'])->name('orders.confirm');

    // --- Consumer Marketplace Portal ---
    Route::get('/marketplace/profile', [ConsumerPortalController::class, 'editProfile'])->name('marketplace.profile.edit');
    Route::patch('/marketplace/profile', [ConsumerPortalController::class, 'updateProfile'])->name('marketplace.profile.update');
    Route::get('/marketplace/notifications', [ConsumerPortalController::class, 'notifications'])->name('marketplace.notifications');
    Route::post('/marketplace/complaints', [ConsumerPortalController::class, 'storeComplaint'])->name('marketplace.complaints.store');
    Route::get('/marketplace/ratings', [ConsumerPortalController::class, 'ratings'])->name('marketplace.ratings');
    Route::post('/marketplace/ratings/{delivery}', [ConsumerPortalController::class, 'storeRating'])->name('marketplace.ratings.store');
    
    // --- Old Routes (kept for backward compatibility) ---

    // --- Subscription & Payment System ---
    Route::get('/subscribe', [SubscriptionController::class, 'index'])->name('subscription.index');
    Route::get('/subscribe/pay', [SubscriptionController::class, 'pay'])->name('subscription.pay');
    Route::get('/payment/success', [SubscriptionController::class, 'success'])->name('payment.success');

    // --- Profile Management ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // --- Client Dashboard ---
    Route::get('/client/dashboard', function () {
        $user = Auth::user();
        // Fallback for days remaining
        $daysRemaining = $user->user_subscription_end ? now()->diffInDays($user->user_subscription_end, false) : 30;
        $daysRemaining = $daysRemaining > 0 ? (int)$daysRemaining : 0;
        
        return view('client.dashboard', compact('daysRemaining'));

    })->name('client.dashboard');
}); // End of authenticated routes group

/*
|--------------------------------------------------------------------------
| INVENTORY SYSTEM TESTING ROUTES
|--------------------------------------------------------------------------
*/
Route::get('/test-inventory/all', [InventoryTestController::class, 'testAll'])->name('test.inventory.all');
Route::get('/test-inventory/egg-grading', [InventoryTestController::class, 'testEggGrading'])->name('test.inventory.egg');
Route::get('/test-inventory/livestock', [InventoryTestController::class, 'testLivestockInventory'])->name('test.inventory.livestock');
Route::get('/test-inventory/dashboard', [InventoryTestController::class, 'testDashboardService'])->name('test.inventory.dashboard');
Route::get('/test-inventory/deduction', [InventoryTestController::class, 'testInventoryDeduction'])->name('test.inventory.deduction');

/*
|--------------------------------------------------------------------------
| SUBSCRIPTION PAYMENT FIX TESTING ROUTES
|--------------------------------------------------------------------------
*/
Route::get('/subscription-test/run-all', [SubscriptionTestController::class, 'runAllTests'])->name('test.subscription.all');
Route::get('/subscription-test/verify-cache-clear', [SubscriptionTestController::class, 'verifyCacheClear'])->name('test.subscription.cache');
Route::get('/subscription-test/verify-limits', [SubscriptionTestController::class, 'verifyLimits'])->name('test.subscription.limits');
Route::get('/subscription-test/verify-active-query', [SubscriptionTestController::class, 'verifyActiveQuery'])->name('test.subscription.query');
Route::get('/subscription-test/simulate-payment-flow', [SubscriptionTestController::class, 'simulatePaymentFlow'])->name('test.subscription.payment');

/*
|--------------------------------------------------------------------------
| SUBSCRIPTION DIAGNOSTICS & FIX ROUTES
|--------------------------------------------------------------------------
*/
Route::get('/debug-subscription-system', [SubscriptionDiagnosticsController::class, 'diagnose'])->name('debug.subscription.diagnose');
Route::get('/debug-subscription-mismatches', [SubscriptionDiagnosticsController::class, 'findMissingSubscriptions'])->name('debug.subscription.mismatches');
Route::post('/fix-farm-subscription/{farm_owner_id}/{plan?}', [SubscriptionDiagnosticsController::class, 'fixSubscription'])->name('debug.subscription.fix');
Route::post('/fix-all-subscriptions', [SubscriptionDiagnosticsController::class, 'fixAllMismatched'])->name('debug.subscription.fix.all');

require __DIR__.'/auth.php';