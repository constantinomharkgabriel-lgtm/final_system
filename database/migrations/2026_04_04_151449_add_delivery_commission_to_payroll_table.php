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
        Schema::table('payroll', function (Blueprint $table) {
            // Add if not exists to make migration idempotent
            if (!Schema::hasColumn('payroll', 'delivery_count')) {
                $table->integer('delivery_count')->default(0)->after('overtime_hours');
            }
            if (!Schema::hasColumn('payroll', 'delivery_commission')) {
                $table->decimal('delivery_commission', 12, 2)->default(0)->after('holiday_pay');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll', function (Blueprint $table) {
            if (Schema::hasColumn('payroll', 'delivery_count')) {
                $table->dropColumn('delivery_count');
            }
            if (Schema::hasColumn('payroll', 'delivery_commission')) {
                $table->dropColumn('delivery_commission');
            }
        });
    }
};
