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
        // Add unique constraint to phone columns if not already present
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'phone')) {
            try {
                // Try to add unique constraint if not already present
                Schema::table('users', function (Blueprint $table) {
                    $table->unique('phone', 'users_phone_unique')->nullable();
                });
            } catch (\Exception $e) {
                // Constraint might already exist, skip
            }
        }

        if (Schema::hasTable('drivers') && Schema::hasColumn('drivers', 'phone')) {
            try {
                Schema::table('drivers', function (Blueprint $table) {
                    $table->unique('phone', 'drivers_phone_unique')->nullable();
                });
            } catch (\Exception $e) {
                // Constraint might already exist, skip
            }
        }

        if (Schema::hasTable('suppliers') && Schema::hasColumn('suppliers', 'phone')) {
            try {
                Schema::table('suppliers', function (Blueprint $table) {
                    $table->unique('phone', 'suppliers_phone_unique')->nullable();
                });
            } catch (\Exception $e) {
                // Constraint might already exist, skip
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the unique constraints
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'phone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['phone']);
            });
        }

        if (Schema::hasTable('drivers') && Schema::hasColumn('drivers', 'phone')) {
            Schema::table('drivers', function (Blueprint $table) {
                $table->dropUnique(['phone']);
            });
        }

        if (Schema::hasTable('suppliers') && Schema::hasColumn('suppliers', 'phone')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->dropUnique(['phone']);
            });
        }
    }
};
