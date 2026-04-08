<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // For PostgreSQL: modify the check constraint to include 'free'
        // First drop the existing enum constraint
        DB::statement("
            ALTER TABLE subscriptions 
            DROP CONSTRAINT IF EXISTS subscriptions_plan_type_check CASCADE;
        ");
        
        // Add new constraint that includes 'free'
        DB::statement("
            ALTER TABLE subscriptions 
            ADD CONSTRAINT subscriptions_plan_type_check 
            CHECK (plan_type IN ('free', 'starter', 'professional', 'enterprise'));
        ");
    }

    public function down(): void
    {
        // Revert to original constraint
        DB::statement("
            ALTER TABLE subscriptions 
            DROP CONSTRAINT IF EXISTS subscriptions_plan_type_check CASCADE;
        ");
        
        DB::statement("
            ALTER TABLE subscriptions 
            ADD CONSTRAINT subscriptions_plan_type_check 
            CHECK (plan_type IN ('starter', 'professional', 'enterprise'));
        ");
    }
};


