<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'logistics_staff', 'display_name' => 'Logistics Staff', 'description' => 'Manages drivers and deliveries'],
            ['name' => 'driver', 'display_name' => 'Driver', 'description' => 'Performs delivery operations'],
            ['name' => 'hr_staff', 'display_name' => 'HR Staff', 'description' => 'Manages employees and attendance'],
            ['name' => 'finance_staff', 'display_name' => 'Finance Staff', 'description' => 'Manages payroll and finances'],
            ['name' => 'farm_operations', 'display_name' => 'Farm Operations', 'description' => 'Manages farm operations'],
            ['name' => 'admin', 'display_name' => 'Administrator', 'description' => 'System administrator'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                ['display_name' => $role['display_name'], 'description' => $role['description']]
            );
        }
    }
}
