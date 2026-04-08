<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix the drivers table unique constraint on phone
        // Change from drivers_phone_unique (which allows only one NULL)
        // to a composite constraint on (farm_owner_id, phone) to allow multiple NULL phones per farm owner
        
        if (Schema::hasTable('drivers')) {
            try {
                // Drop the old unique constraint if it exists
                Schema::table('drivers', function (Blueprint $table) {
                    try {
                        $table->dropUnique('drivers_phone_unique');
                    } catch (\Exception $e) {
                        // Constraint might not exist, continue
                    }
                });
            } catch (\Exception $e) {
                // Table might not exist or constraint might not exist, continue
            }
            
            // Add new composite unique constraint
            try {
                $connection = DB::connection()->getDriverName();
                
                // For SQLite/PostgreSQL, use raw SQL
                if (in_array($connection, ['sqlite', 'pgsql'])) {
                    DB::statement('
                        CREATE UNIQUE INDEX drivers_farm_phone_unique 
                        ON drivers(farm_owner_id, phone) 
                        WHERE phone IS NOT NULL
                    ');
                } else {
                    // For MySQL, use this approach
                    DB::statement('
                        ALTER TABLE drivers 
                        ADD CONSTRAINT drivers_farm_phone_unique 
                        UNIQUE (farm_owner_id, phone)
                    ');
                }
            } catch (\Exception $e) {
                // Constraint might already exist, log it
                \Illuminate\Support\Facades\Log::warning('Could not add composite unique constraint: ' . $e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('drivers')) {
            try {
                Schema::table('drivers', function (Blueprint $table) {
                    try {
                        $table->dropUnique('drivers_farm_phone_unique');
                    } catch (\Exception $e) {
                        // Constraint might not exist
                    }
                });
                
                // Re-add the original constraint for reverse
                Schema::table('drivers', function (Blueprint $table) {
                    $table->unique('phone', 'drivers_phone_unique')->nullable();
                });
            } catch (\Exception $e) {
                // Constraints might not exist
            }
        }
    }
};
