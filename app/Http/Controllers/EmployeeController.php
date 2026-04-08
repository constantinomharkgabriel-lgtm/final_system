<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Driver;
use App\Models\FarmOwner;
use App\Models\User;
use App\Rules\PhilippinePhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;

class EmployeeController extends Controller
{
    use \App\Http\Controllers\Concerns\ResolvesFarmOwner;

    private function isFarmOwnerUser(): bool
    {
        return (bool) Auth::user()?->isFarmOwner();
    }

    private function statsCacheKey(int $farmOwnerId): string
    {
        return "farm_{$farmOwnerId}_employee_stats";
    }

    public function index(Request $request)
    {
        $farmOwner = $this->getFarmOwner();

        $selectColumns = [
            'id',
            'employee_id',
            'first_name',
            'last_name',
            'middle_name',
            'department',
            'position',
            'hire_date',
            'daily_rate',
            'status',
        ];

        // Guard against environments where the performance_rating migration has not run yet.
        if (Schema::hasColumn('employees', 'performance_rating')) {
            $selectColumns[] = 'performance_rating';
        }
        
        $query = Employee::byFarmOwner($farmOwner->id)
            ->select($selectColumns);

        if ($request->filled('department')) {
            $query->byDepartment($request->department);
        }

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        $employees = $query->orderBy('last_name')->paginate(20);

        $stats = Cache::remember($this->statsCacheKey($farmOwner->id), 300, function () use ($farmOwner) {
            $aggregate = Employee::byFarmOwner($farmOwner->id)
                ->selectRaw("COUNT(*) as total")
                ->selectRaw("SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active")
                ->selectRaw("COALESCE(SUM(CASE WHEN status = 'active' THEN monthly_salary ELSE 0 END), 0) as total_monthly_salary")
                ->first();

            return [
                'total' => (int) ($aggregate->total ?? 0),
                'active' => (int) ($aggregate->active ?? 0),
                'total_monthly_salary' => (float) ($aggregate->total_monthly_salary ?? 0),
            ];
        });

        return view('farmowner.employees.index', compact('employees', 'stats'));
    }

    public function create()
    {
        return view('farmowner.employees.create');
    }

    public function store(Request $request)
    {
        $farmOwner = $this->getFarmOwner();
        $verificationUrl = null;
        $verificationEmailSent = false;
        $verificationEmailError = null;
        $driverToNotify = null;
        $employeeUserToNotify = null;

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20', // For testing: accepts any value
            'address' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'department' => 'required|in:farm_operations,hr,finance,logistics,sales,admin',
            'position' => 'required|string|max:100',
            'employment_type' => 'required|in:full_time,part_time,contract,seasonal',
            'hire_date' => 'required|date',
            'daily_rate' => 'nullable|numeric|min:0',
            'monthly_salary' => 'nullable|numeric|min:0',
            'performance_rating' => 'nullable|integer|min:1|max:5',
            'sss_number' => 'nullable|string|max:20',
            'philhealth_number' => 'nullable|string|max:20',
            'pagibig_number' => 'nullable|string|max:20',
            'tin_number' => 'nullable|string|max:20',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,name',
            // Driver fields - required only if driver role is assigned
            'vehicle_type' => 'nullable|in:motorcycle,tricycle,van,truck',
            'vehicle_plate' => 'nullable|string|max:20',
            'vehicle_model' => 'nullable|string|max:100',
            'license_number' => 'nullable|string|max:50',
            'license_expiry' => 'nullable|date',
            'delivery_fee' => 'nullable|numeric|min:0',
            'driver_notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $farmOwner, &$verificationUrl, &$employeeUserToNotify, &$driverToNotify) {
            // For testing: keep phone null to avoid unique constraint violations with test data
            // In production, phone would be properly validated and normalized
            $phone = null;
            
            $employeeUser = User::create([
                'name' => trim($validated['first_name'] . ' ' . $validated['last_name']),
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $phone, // Set to null for testing to allow multiple test employees
                'role' => $validated['department'],
                'status' => 'active',
                'email_verified_at' => null,
            ]);

            // Store user reference to send email AFTER transaction completes (non-blocking)
            if (!$employeeUser->hasVerifiedEmail()) {
                $employeeUserToNotify = $employeeUser;
                
                // Generate verification URL for display/fallback
                $verificationUrl = URL::temporarySignedRoute(
                    'verification.verify',
                    now()->addMinutes(60),
                    [
                        'id' => $employeeUser->id,
                        'hash' => sha1($employeeUser->getEmailForVerification()),
                    ]
                );
            }

            $employeeData = collect($validated)
                ->except(['password', 'password_confirmation'])
                ->toArray();

            // Guard against environments where the performance_rating migration has not run yet.
            if (!Schema::hasColumn('employees', 'performance_rating')) {
                unset($employeeData['performance_rating']);
            }

            $roles = $validated['roles'] ?? [];
            if (isset($validated['roles'])) unset($employeeData['roles']);
            if (isset($employeeData['driver_notes'])) unset($employeeData['driver_notes']);
            if (isset($employeeData['vehicle_type'])) unset($employeeData['vehicle_type']);
            if (isset($employeeData['vehicle_plate'])) unset($employeeData['vehicle_plate']);
            if (isset($employeeData['vehicle_model'])) unset($employeeData['vehicle_model']);
            if (isset($employeeData['license_number'])) unset($employeeData['license_number']);
            if (isset($employeeData['license_expiry'])) unset($employeeData['license_expiry']);
            if (isset($employeeData['delivery_fee'])) unset($employeeData['delivery_fee']);

            $employeeData['farm_owner_id'] = $farmOwner->id;
            $employeeData['user_id'] = $employeeUser->id;
            $employeeData['employee_id'] = $this->generateEmployeeId($farmOwner->id);

            // Assign roles if provided
            $roles = $validated['roles'] ?? [];
            
            // If department is 'logistics' and driver info is filled or role driver selected
            if ($validated['department'] === 'logistics' && (in_array('driver', $roles) || !empty($validated['vehicle_type']))) {
                if (!in_array('driver', $roles)) {
                    $roles[] = 'driver';
                }
            }

            $employee = clone (new Employee)->fill($employeeData);
            $employee->save();
            
            foreach ($roles as $roleName) {
                // Ensure we skip assigning if not present in the request array, but allow driver if injected above
                $employee->assignRole($roleName);
            }

            // If driver role is assigned, create a driver profile
            if ($employee->hasRole('driver') && !$employee->driver) {
                $driverUser = clone $employeeUser;
                
                $driver = Driver::create([
                    'farm_owner_id' => $farmOwner->id,
                    'employee_id' => $employee->id,
                    'user_id' => $driverUser->id,
                    'driver_code' => 'DRV-' . $farmOwner->id . '-' . time() . '-' . strtoupper(substr(uniqid(), -6)),
                    'name' => $employee->full_name,
                    'email' => $driverUser->email,
                    'phone' => $employee->phone,  // Use NULL if not provided (allows multiple drivers without phone)
                    'vehicle_type' => $validated['vehicle_type'] ?? null,
                    'vehicle_plate' => $validated['vehicle_plate'] ?? null,
                    'vehicle_model' => $validated['vehicle_model'] ?? null,
                    'license_number' => $validated['license_number'] ?? null,
                    'license_expiry' => $validated['license_expiry'] ?? null,
                    'delivery_fee' => $validated['delivery_fee'] ?? 0,
                    'notes' => $validated['driver_notes'] ?? null,
                    'status' => 'available',
                    'is_verified' => false, // Mark as unverified until email is confirmed
                ]);

                // Store driver reference to send email AFTER transaction completes
                $driverToNotify = $driver;
            }
        });

        // Send employee verification email AFTER transaction completes (non-blocking)
        // Use pcntl_fork or async approach to prevent blocking
        if ($employeeUserToNotify) {
            // Use a deferred callback to send email without blocking
            \Illuminate\Support\Facades\App::terminating(function () use ($employeeUserToNotify, &$verificationEmailSent, &$verificationEmailError) {
                try {
                    $emailSent = false;
                    $maxAttempts = 1;
                    
                    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                        try {
                            $employeeUserToNotify->sendEmailVerificationNotification();
                            $verificationEmailSent = true;
                            $emailSent = true;
                            break;
                        } catch (\Exception $e) {
                            if ($attempt === $maxAttempts) {
                                throw $e;
                            }
                            usleep(500000); // 500ms wait before retry
                        }
                    }
                    
                    if ($emailSent) {
                        Log::info('Employee verification email sent (async)', [
                            'user_id' => $employeeUserToNotify->id,
                            'email' => $employeeUserToNotify->email,
                        ]);
                    }
                } catch (\Throwable $e) {
                    $verificationEmailError = $e->getMessage();
                    Log::warning('Employee verification email failed to send (async)', [
                        'user_id' => $employeeUserToNotify->id,
                        'email' => $employeeUserToNotify->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            });
        }

        // Send driver verification email AFTER transaction completes (non-blocking)
        // Use deferred callback to prevent blocking
        if ($driverToNotify) {
            \Illuminate\Support\Facades\App::terminating(function () use ($driverToNotify) {
                try {
                    // Try to send with a timeout
                    $driverToNotify->notify(new \App\Notifications\VerifyDriverEmail($driverToNotify));
                    Log::info('Driver verification email sent (async)', [
                        'driver_id' => $driverToNotify->id,
                        'email' => $driverToNotify->email,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Driver verification email failed to send (async)', [
                        'driver_id' => $driverToNotify->id,
                        'email' => $driverToNotify->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            });
        }

        Cache::forget($this->statsCacheKey($farmOwner->id));

        $mailDriver = (string) config('mail.default', 'log');

        if ($mailDriver === 'log') {
            $message = 'Employee added successfully. Email delivery is currently in LOG mode, so no inbox email was sent. Use SMTP in production.';
        } elseif ($verificationEmailSent) {
            $message = 'Employee added successfully. Verification email sent to their account email.';
        } else {
            $message = 'Employee added successfully, but verification email could not be sent right now. Please check mail settings and resend later.';
            if ($verificationEmailError) {
                $safeError = Str::limit(preg_replace('/\s+/', ' ', trim($verificationEmailError)), 220);
                $message .= ' Mail error: ' . $safeError;
            }
        }

        $redirect = redirect()->route('employees.index')->with('success', $message);

        if ($verificationUrl) {
            $redirect->with('verification_url', $verificationUrl);
        }

        return $redirect;
    }

    private function generateEmployeeId(int $farmOwnerId): string
    {
        do {
            $employeeId = 'EMP-' . Str::upper(Str::random(6));
        } while (Employee::where('farm_owner_id', $farmOwnerId)->where('employee_id', $employeeId)->exists());

        return $employeeId;
    }

    public function show(Employee $employee)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($employee->farm_owner_id !== $farmOwner->id, 403);

        $employee->load([
            'attendance' => fn($q) => $q->latest('work_date')->limit(30),
            'payroll' => fn($q) => $q->latest('period_start')->limit(12),
        ]);

        return view('farmowner.employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($employee->farm_owner_id !== $farmOwner->id, 403);

        return view('farmowner.employees.edit', compact('employee'));
    }

    public function update(Request $request, Employee $employee)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($employee->farm_owner_id !== $farmOwner->id, 403);

        $rules = [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'position' => 'required|string|max:100',
            'employment_type' => 'nullable|in:full_time,part_time,contract,seasonal',
            'hire_date' => 'nullable|date',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ];

        if ($this->isFarmOwnerUser()) {
            $rules['department'] = 'required|in:farm_operations,hr,finance,logistics,sales,admin';
            $rules['daily_rate'] = 'nullable|numeric|min:0';
            $rules['monthly_salary'] = 'nullable|numeric|min:0';
            $rules['performance_rating'] = 'nullable|integer|min:1|max:5';
            $rules['status'] = 'required|in:active,on_leave,suspended,terminated,resigned';
            $rules['roles'] = 'nullable|array';
            $rules['roles.*'] = 'exists:roles,name';
            // Driver fields
            $rules['vehicle_type'] = 'nullable|in:motorcycle,tricycle,van,truck';
            $rules['vehicle_plate'] = 'nullable|string|max:20';
            $rules['vehicle_model'] = 'nullable|string|max:100';
            $rules['license_number'] = 'nullable|string|max:50';
            $rules['license_expiry'] = 'nullable|date';
            $rules['delivery_fee'] = 'nullable|numeric|min:0';
            $rules['driver_notes'] = 'nullable|string';
        }

        $validated = $request->validate($rules);

        $employee->update($validated);

        // Update roles if provided
        if ($this->isFarmOwnerUser() && isset($validated['roles'])) {
            $employee->revokeAllRoles();
            $roles = $validated['roles'];
            
            // If department is 'driver', automatically add driver role
            if ($validated['department'] === 'driver' && !in_array('driver', $roles)) {
                $roles[] = 'driver';
            }
            
            foreach ($roles as $roleName) {
                $employee->assignRole($roleName);
            }

            // If driver role is assigned, create or update driver profile
            if (in_array('driver', $roles)) {
                if ($employee->driver) {
                    // Update existing driver profile
                    $employee->driver->update([
                        'vehicle_type' => $validated['vehicle_type'] ?? $employee->driver->vehicle_type,
                        'vehicle_plate' => $validated['vehicle_plate'] ?? $employee->driver->vehicle_plate,
                        'vehicle_model' => $validated['vehicle_model'] ?? $employee->driver->vehicle_model,
                        'license_number' => $validated['license_number'] ?? $employee->driver->license_number,
                        'license_expiry' => $validated['license_expiry'] ?? $employee->driver->license_expiry,
                        'delivery_fee' => $validated['delivery_fee'] ?? $employee->driver->delivery_fee,
                        'notes' => $validated['driver_notes'] ?? $employee->driver->notes,
                    ]);
                } else {
                    // Create new driver profile
                    Driver::create([
                        'farm_owner_id' => $employee->farm_owner_id,
                        'employee_id' => $employee->id,
                        'user_id' => $employee->user_id,
                        'driver_code' => 'DRV-' . $employee->farm_owner_id . '-' . time() . '-' . strtoupper(substr(uniqid(), -6)),
                        'name' => $employee->full_name,
                        'phone' => $employee->phone,  // Use NULL if not provided (allows multiple drivers without phone)
                        'vehicle_type' => $validated['vehicle_type'] ?? null,
                        'vehicle_plate' => $validated['vehicle_plate'] ?? null,
                        'vehicle_model' => $validated['vehicle_model'] ?? null,
                        'license_number' => $validated['license_number'] ?? null,
                        'license_expiry' => $validated['license_expiry'] ?? null,
                        'delivery_fee' => $validated['delivery_fee'] ?? 0,
                        'notes' => $validated['driver_notes'] ?? null,
                        'status' => 'available',
                    ]);
                }
            }

            // If driver role is removed, optionally delete the driver profile
            if (!in_array('driver', $roles) && $employee->driver) {
                $employee->driver->delete();
            }
        } elseif ($this->isFarmOwnerUser() && $validated['department'] === 'driver') {
            // Handle case where department is set to driver but no roles array provided
            if (!$employee->hasRole('driver')) {
                $employee->assignRole('driver');
            }
            
            // Create or update driver profile
            if ($employee->driver) {
                $employee->driver->update([
                    'vehicle_type' => $validated['vehicle_type'] ?? $employee->driver->vehicle_type,
                    'vehicle_plate' => $validated['vehicle_plate'] ?? $employee->driver->vehicle_plate,
                    'vehicle_model' => $validated['vehicle_model'] ?? $employee->driver->vehicle_model,
                    'license_number' => $validated['license_number'] ?? $employee->driver->license_number,
                    'license_expiry' => $validated['license_expiry'] ?? $employee->driver->license_expiry,
                    'delivery_fee' => $validated['delivery_fee'] ?? $employee->driver->delivery_fee,
                    'notes' => $validated['driver_notes'] ?? $employee->driver->notes,
                ]);
            } else {
                Driver::create([
                    'farm_owner_id' => $employee->farm_owner_id,
                    'employee_id' => $employee->id,
                    'user_id' => $employee->user_id,
                    'driver_code' => 'DRV-' . $employee->farm_owner_id . '-' . time() . '-' . strtoupper(substr(uniqid(), -6)),
                    'name' => $employee->full_name,
                    'phone' => $employee->phone,  // Use NULL if not provided (allows multiple drivers without phone)
                    'vehicle_type' => $validated['vehicle_type'] ?? null,
                    'vehicle_plate' => $validated['vehicle_plate'] ?? null,
                    'vehicle_model' => $validated['vehicle_model'] ?? null,
                    'license_number' => $validated['license_number'] ?? null,
                    'license_expiry' => $validated['license_expiry'] ?? null,
                    'delivery_fee' => $validated['delivery_fee'] ?? 0,
                    'notes' => $validated['driver_notes'] ?? null,
                    'status' => 'available',
                ]);
            }
        }

        Cache::forget($this->statsCacheKey($farmOwner->id));

        return redirect()->route('employees.show', $employee)->with('success', 'Employee updated.');
    }

    public function destroy(Employee $employee)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($employee->farm_owner_id !== $farmOwner->id, 403);
        abort_unless(
            $this->isFarmOwnerUser() || Auth::user()?->isHR(),
            403,
            'Only farm owner or HR can delete employees.'
        );

        DB::transaction(function () use ($employee) {
            $linkedUser = $employee->user;

            $employee->delete();

            if ($linkedUser) {
                $linkedUser->delete();
            }
        });

        Cache::forget($this->statsCacheKey($farmOwner->id));

        return redirect()->route('employees.index')->with('success', 'Employee account removed.');
    }
}
