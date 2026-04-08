<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_owner_id', 'user_id', 'employee_id', 'first_name', 'last_name', 'middle_name',
        'email', 'phone', 'address', 'birth_date', 'gender', 'emergency_contact_name',
        'emergency_contact_phone', 'department', 'position', 'employment_type', 'hire_date',
        'end_date', 'daily_rate', 'monthly_salary', 'sss_number', 'philhealth_number',
        'performance_rating', 'pagibig_number', 'tin_number', 'bank_name', 'bank_account_number', 'status', 'notes'
    ];

    protected $casts = [
        'birth_date' => 'date',
        'hire_date' => 'date',
        'end_date' => 'date',
        'daily_rate' => 'decimal:2',
        'monthly_salary' => 'decimal:2',
        'performance_rating' => 'integer',
    ];

    // Relationships
    public function farmOwner()
    {
        return $this->belongsTo(FarmOwner::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class);
    }

    public function payroll()
    {
        return $this->hasMany(Payroll::class);
    }

    public function driver()
    {
        return $this->hasOne(Driver::class);
    }

    public function employeeRoles()
    {
        return $this->hasMany(EmployeeRole::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'employee_roles', 'employee_id', 'role_id');
    }

    // Query Scopes
    public function scopeByFarmOwner(Builder $query, int $farmOwnerId)
    {
        return $query->where('farm_owner_id', $farmOwnerId);
    }

    public function scopeActive(Builder $query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByDepartment(Builder $query, string $department)
    {
        $normalizedDepartment = (string) Str::of($department)->trim()->lower()->replace(' ', '_');

        return $query->whereRaw("LOWER(REPLACE(department, ' ', '_')) = ?", [$normalizedDepartment]);
    }

    public function scopeByStatus(Builder $query, string $status)
    {
        $normalizedStatus = (string) Str::of($status)->trim()->lower()->replace(' ', '_');

        return $query->whereRaw("LOWER(REPLACE(status, ' ', '_')) = ?", [$normalizedStatus]);
    }

    // Computed
    public function getFullNameAttribute(): string
    {
        // Handle missing middle_name when using select() queries
        $firstName = $this->first_name ?? '';
        $middleName = $this->middle_name ?? '';
        $lastName = $this->last_name ?? '';
        
        return trim("{$firstName} {$middleName} {$lastName}");
    }

    public function getYearsOfServiceAttribute(): float
    {
        $endDate = $this->end_date ?? now();
        return round($this->hire_date->diffInYears($endDate), 1);
    }

    public function getAgeAttribute(): ?int
    {
        return $this->birth_date?->age;
    }

    // Methods
    public function terminate(string $reason = null): void
    {
        $this->update([
            'status' => 'terminated',
            'end_date' => today(),
            'notes' => $this->notes . "\n[Terminated] " . $reason
        ]);
    }

    public function calculateDailyRate(): float
    {
        // Standard Philippines: monthly salary / 22 working days (DTI/BIR standard)
        return $this->monthly_salary > 0 ? round($this->monthly_salary / 22, 2) : (float) $this->daily_rate;
    }

    public function calculateMonthlySalary(): float
    {
        // Reverse calculation: daily_rate × 22 working days (Philippines standard)
        return $this->daily_rate > 0 ? round($this->daily_rate * 22, 2) : (float) $this->monthly_salary;
    }

    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    public function assignRole(string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();
        if ($role && !$this->hasRole($roleName)) {
            $this->roles()->attach($role->id);
        }
    }

    public function removeRole(string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();
        if ($role) {
            $this->roles()->detach($role->id);
        }
    }

    public function revokeAllRoles(): void
    {
        $this->roles()->detach();
    }
}
