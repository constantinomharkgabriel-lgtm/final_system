<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql') {
            // PostgreSQL - check if indexes exist before creating
            $indexes = \Illuminate\Support\Facades\DB::select("
                SELECT indexname FROM pg_indexes 
                WHERE tablename = 'payroll'
            ");
            $indexNames = collect($indexes)->pluck('indexname')->toArray();

            Schema::table('payroll', function (Blueprint $table) use ($indexNames) {
                if (!in_array('payroll_farm_owner_id_index', $indexNames)) {
                    $table->index('farm_owner_id');
                }
                if (!in_array('payroll_employee_id_index', $indexNames)) {
                    $table->index('employee_id');
                }
                if (!in_array('payroll_workflow_status_index', $indexNames)) {
                    $table->index('workflow_status');
                }
                if (!in_array('payroll_status_index', $indexNames)) {
                    $table->index('status');
                }
                if (!in_array('payroll_farm_owner_id_workflow_status_index', $indexNames)) {
                    $table->index(['farm_owner_id', 'workflow_status']);
                }
                if (!in_array('payroll_period_start_index', $indexNames)) {
                    $table->index('period_start');
                }
                if (!in_array('payroll_created_at_index', $indexNames)) {
                    $table->index('created_at');
                }
            });
        } else {
            // Other databases
            Schema::table('payroll', function (Blueprint $table) {
                $table->index('farm_owner_id');
                $table->index('employee_id');
                $table->index('workflow_status');
                $table->index('status');
                $table->index(['farm_owner_id', 'workflow_status']);
                $table->index('period_start');
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll', function (Blueprint $table) {
            $table->dropIndex('payroll_farm_owner_id_index');
            $table->dropIndex('payroll_employee_id_index');
            $table->dropIndex('payroll_workflow_status_index');
            $table->dropIndex('payroll_status_index');
            $table->dropIndex('payroll_farm_owner_id_workflow_status_index');
            $table->dropIndex('payroll_period_start_index');
            $table->dropIndex('payroll_created_at_index');
        });
    }
};
