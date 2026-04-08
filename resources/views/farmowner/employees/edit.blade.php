@extends(auth()->user()?->isHR() ? 'hr.layouts.app' : 'farmowner.layouts.app')

@section('title', 'Edit Employee')
@section('header', 'Edit Employee')

@section('content')
<div class="max-w-2xl">
    <form action="{{ route('employees.update', $employee) }}" method="POST" class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        @csrf
        @method('PUT')
        
        <!-- Basic Info -->
        <h3 class="font-semibold text-lg mb-4 pb-2 border-b border-gray-600">Basic Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">First Name *</label>
                <input type="text" name="first_name" value="{{ old('first_name', $employee->first_name) }}" required
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Last Name *</label>
                <input type="text" name="last_name" value="{{ old('last_name', $employee->last_name) }}" required
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Phone (PH) <span class="text-xs text-gray-400">(testing)</span></label>
                <input type="text" name="phone" value="{{ old('phone', $employee->phone) }}" placeholder="09123456789 (for testing - optional)"
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500">
                <p class="text-xs text-gray-400 mt-1">💡 Any test number or leave blank</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', $employee->email) }}"
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-300 mb-1">Address</label>
                <textarea name="address" rows="2"
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500">{{ old('address', $employee->address) }}</textarea>
            </div>
        </div>

        <!-- Employment Details -->
        <h3 class="font-semibold text-lg mb-4 pb-2 border-b border-gray-600">Employment Details</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            @if(Auth::user()?->isFarmOwner())
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Department *</label>
                <select name="department" id="department-select" required
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black focus:ring-2 focus:ring-green-500">
                    <option value="farm_operations" {{ old('department', $employee->department) === 'farm_operations' ? 'selected' : '' }}>Farm Operations</option>
                    <option value="logistics" {{ old('department', $employee->department) === 'logistics' ? 'selected' : '' }}>Logistics</option>
                    <option value="hr" {{ old('department', $employee->department) === 'hr' ? 'selected' : '' }}>HR</option>
                    <option value="finance" {{ old('department', $employee->department) === 'finance' ? 'selected' : '' }}>Finance</option>
                    <option value="sales" {{ old('department', $employee->department) === 'sales' ? 'selected' : '' }}>Sales</option>
                    <option value="admin" {{ old('department', $employee->department) === 'admin' ? 'selected' : '' }}>Admin</option>
                </select>
            </div>
            @else
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Department</label>
                <input type="text" value="{{ ucfirst(str_replace('_', ' ', $employee->department)) }}" disabled
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-gray-200 text-gray-700 cursor-not-allowed">
            </div>
            @endif
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Position *</label>
                <input type="text" name="position" value="{{ old('position', $employee->position) }}" required
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Employment Type</label>
                <select name="employment_type"
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black focus:ring-2 focus:ring-green-500">
                    <option value="full_time" {{ old('employment_type', $employee->employment_type) === 'full_time' ? 'selected' : '' }}>Full-time</option>
                    <option value="part_time" {{ old('employment_type', $employee->employment_type) === 'part_time' ? 'selected' : '' }}>Part-time</option>
                    <option value="contract" {{ old('employment_type', $employee->employment_type) === 'contract' ? 'selected' : '' }}>Contract</option>
                    <option value="seasonal" {{ old('employment_type', $employee->employment_type) === 'seasonal' ? 'selected' : '' }}>Seasonal</option>
                </select>
            </div>
            @if(Auth::user()?->isFarmOwner())
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Status</label>
                <select name="status"
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black focus:ring-2 focus:ring-green-500">
                    <option value="active" {{ old('status', $employee->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="on_leave" {{ old('status', $employee->status) === 'on_leave' ? 'selected' : '' }}>On Leave</option>
                    <option value="suspended" {{ old('status', $employee->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="terminated" {{ old('status', $employee->status) === 'terminated' ? 'selected' : '' }}>Terminated</option>
                    <option value="resigned" {{ old('status', $employee->status) === 'resigned' ? 'selected' : '' }}>Resigned</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Daily Rate (₱) *</label>
                <input type="number" name="daily_rate" id="edit_daily_rate" value="{{ old('daily_rate', $employee->daily_rate) }}" step="0.01" required
                    placeholder="Employee's daily rate"
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500">
                <p class="text-xs text-gray-400 mt-1">💡 Philippines Standard: Monthly = Daily Rate × 22 working days</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Monthly Salary (₱) <span class="text-gray-400 text-xs">(reference)</span></label>
                <input type="number" name="monthly_salary_display" id="edit_monthly_salary" value="{{ old('monthly_salary', $employee->monthly_salary) }}" step="0.01" readonly
                    placeholder="Calculated from daily rate × 22"
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-gray-700 text-gray-300 cursor-not-allowed placeholder-gray-500">
                <p id="edit-salary-info" class="text-xs text-gray-400 mt-1">Reference calculation</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Performance Rating (1-5)</label>
                <select name="performance_rating" class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black focus:ring-2 focus:ring-green-500">
                    @for($i = 1; $i <= 5; $i++)
                        <option value="{{ $i }}" {{ (int) old('performance_rating', $employee->performance_rating ?? 3) === $i ? 'selected' : '' }}>{{ $i }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Date Hired</label>
                <input type="date" name="hire_date" value="{{ old('hire_date', $employee->hire_date?->format('Y-m-d')) }}"
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black focus:ring-2 focus:ring-green-500">
            </div>
            @else
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Status</label>
                <input type="text" value="{{ ucfirst(str_replace('_', ' ', $employee->status)) }}" disabled
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-gray-200 text-gray-700 cursor-not-allowed">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Daily Rate (₱)</label>
                <input type="text" value="₱{{ number_format($employee->daily_rate ?? 0, 2) }}" disabled
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-gray-200 text-gray-700 cursor-not-allowed">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Performance Rating</label>
                <input type="text" value="{{ $employee->performance_rating ?? 3 }}/5" disabled
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-gray-200 text-gray-700 cursor-not-allowed">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Hire Date</label>
                <input type="date" name="hire_date" value="{{ old('hire_date', $employee->hire_date?->format('Y-m-d')) }}"
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black focus:ring-2 focus:ring-green-500">
            </div>
            @endif
        </div>

        <!-- Roles & Permissions (Farm Owner Only) -->
        @if(Auth::user()?->isFarmOwner())
        <h3 class="font-semibold text-lg mb-4 pb-2 border-b border-gray-600 mt-6">Roles & Permissions</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            @php
                $availableRoles = \App\Models\Role::all();
                $employeeRoles = $employee->roles()->pluck('name')->toArray();
                $roleDescriptions = [
                    'driver' => 'Can perform deliveries and earn commission',
                    'logistics_staff' => 'Can manage drivers and assign deliveries',
                    'hr_staff' => 'Can manage employees and attendance',
                    'finance_staff' => 'Can process payroll and finances',
                    'farm_operations' => 'Can manage farm operations',
                    'admin' => 'Full system access',
                ];
            @endphp
            @foreach($availableRoles as $role)
                <label class="flex items-center space-x-3 p-3 border border-gray-600 rounded-lg hover:bg-gray-700 cursor-pointer">
                    <input type="checkbox" name="roles[]" value="{{ $role->name }}"
                        {{ in_array($role->name, array_merge($employeeRoles, old('roles', []))) ? 'checked' : '' }}
                        class="w-4 h-4 rounded role-checkbox"
                        data-role="{{ $role->name }}">
                    <div>
                        <div class="text-sm font-medium text-white">{{ $role->display_name }}</div>
                        <div class="text-xs text-gray-400">{{ $roleDescriptions[$role->name] ?? $role->description }}</div>
                    </div>
                </label>
            @endforeach
        </div>

        <!-- Driver Details (Conditional) -->
        <div id="driver-section" class="{{ (in_array('driver', $employeeRoles) || $employee->department === 'driver') ? '' : 'hidden' }} bg-blue-900 bg-opacity-20 border border-blue-700 rounded-lg p-6 mt-6">
            <h4 class="font-medium text-white mb-4 flex items-center">
                <span class="text-blue-400 mr-2">🚗</span>
                Driver Details
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Vehicle Type *</label>
                    <select name="vehicle_type" class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black focus:ring-2 focus:ring-blue-500">
                        <option value="">Select</option>
                        <option value="motorcycle" {{ old('vehicle_type', $employee->driver?->vehicle_type) === 'motorcycle' ? 'selected' : '' }}>Motorcycle</option>
                        <option value="tricycle" {{ old('vehicle_type', $employee->driver?->vehicle_type) === 'tricycle' ? 'selected' : '' }}>Tricycle</option>
                        <option value="van" {{ old('vehicle_type', $employee->driver?->vehicle_type) === 'van' ? 'selected' : '' }}>Van</option>
                        <option value="truck" {{ old('vehicle_type', $employee->driver?->vehicle_type) === 'truck' ? 'selected' : '' }}>Truck</option>
                    </select>
                    @error('vehicle_type')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Plate Number</label>
                    <input type="text" name="vehicle_plate" value="{{ old('vehicle_plate', $employee->driver?->vehicle_plate) }}" placeholder="ABC 1234"
                        class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-blue-500">
                    @error('vehicle_plate')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Vehicle Model</label>
                    <input type="text" name="vehicle_model" value="{{ old('vehicle_model', $employee->driver?->vehicle_model) }}" placeholder="e.g., Honda PCX, Isuzu NPR"
                        class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-blue-500">
                    @error('vehicle_model')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">License Number</label>
                    <input type="text" name="license_number" value="{{ old('license_number', $employee->driver?->license_number) }}"
                        class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-blue-500">
                    @error('license_number')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">License Expiry *</label>
                    <input type="date" name="license_expiry" value="{{ old('license_expiry', $employee->driver?->license_expiry?->format('Y-m-d')) }}"
                        class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black focus:ring-2 focus:ring-blue-500">
                    @error('license_expiry')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Delivery Fee (₱) *</label>
                    <input type="number" name="delivery_fee" value="{{ old('delivery_fee', $employee->driver?->delivery_fee) }}" step="0.01" min="0" placeholder="e.g., 50.00"
                        class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-400 mt-1">💡 Commission earned per completed delivery</p>
                    @error('delivery_fee')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-300 mb-1">Notes</label>
                    <textarea name="driver_notes" rows="3" placeholder="e.g., Vehicle condition notes, special instructions..."
                        class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-blue-500">{{ old('driver_notes', $employee->driver?->notes) }}</textarea>
                    @error('driver_notes')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>
        @endif

        @unless(Auth::user()?->isFarmOwner())
        <div class="mb-6 rounded-lg border border-yellow-700 bg-yellow-900/30 px-4 py-3 text-sm text-yellow-200">
            HR can update general employee details, but only the farm owner can change department, salary, status, or delete employee accounts.
        </div>
        @endunless

        <div class="flex gap-3">
            <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Update Employee</button>
            <a href="{{ route('employees.index') }}" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-500">Cancel</a>
        </div>
    </form>
</div>

<script>
    // Auto-calculate monthly salary from daily rate (22 working days - Philippines standard)
    const editDailyRateInput = document.getElementById('edit_daily_rate');
    const editMonthlySalaryInput = document.getElementById('edit_monthly_salary');
    const editSalaryInfo = document.getElementById('edit-salary-info');
    
    if (editDailyRateInput && editMonthlySalaryInput) {
        function updateEditMonthlySalary() {
            const dailyRate = parseFloat(editDailyRateInput.value) || 0;
            if (dailyRate > 0) {
                const monthlySalary = (dailyRate * 22).toFixed(2);
                editMonthlySalaryInput.value = monthlySalary;
                const formatted = parseFloat(monthlySalary).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                editSalaryInfo.textContent = `✓ Reference: ₱${formatted} (₱${dailyRate.toFixed(2)} × 22 days)`;
                editSalaryInfo.classList.remove('text-gray-400');
                editSalaryInfo.classList.add('text-green-400');
            } else {
                editMonthlySalaryInput.value = '';
                editSalaryInfo.textContent = 'Reference calculation';
                editSalaryInfo.classList.remove('text-green-400');
                editSalaryInfo.classList.add('text-gray-400');
            }
        }
        
        editDailyRateInput.addEventListener('input', updateEditMonthlySalary);
        editDailyRateInput.addEventListener('change', updateEditMonthlySalary);
        window.addEventListener('load', updateEditMonthlySalary);
    }

    // Toggle driver section visibility based on driver role checkbox OR department selection
    const driverSection = document.getElementById('driver-section');
    const roleCheckboxes = document.querySelectorAll('.role-checkbox');
    const departmentSelect = document.getElementById('department-select');
    
    function updateDriverSectionVisibility() {
        // Show driver form if either:
        // 1. Driver role is checked
        // 2. Driver department is selected
        const isDriverRoleSelected = Array.from(roleCheckboxes)
            .some(checkbox => checkbox.dataset.role === 'driver' && checkbox.checked);
        const isDriverDepartmentSelected = departmentSelect && departmentSelect.value === 'driver';
        
        if (isDriverRoleSelected || isDriverDepartmentSelected) {
            driverSection.classList.remove('hidden');
        } else {
            driverSection.classList.add('hidden');
        }
    }
    
    roleCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateDriverSectionVisibility);
    });

    if (departmentSelect) {
        departmentSelect.addEventListener('change', updateDriverSectionVisibility);
    }

    // Initialize visibility on page load
    window.addEventListener('load', updateDriverSectionVisibility);
</script>

@endsection
