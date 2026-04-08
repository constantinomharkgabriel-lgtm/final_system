<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            // Add email for driver login/verification
            $table->string('email')->nullable()->unique()->after('phone');
            
            // Add verification field
            $table->boolean('is_verified')->default(false)->after('email');
            
            // Track when verification was completed
            $table->timestamp('verified_at')->nullable()->after('is_verified');
            
            // Index for queries
            $table->index(['farm_owner_id', 'is_verified']);
        });
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropIndex(['farm_owner_id', 'is_verified']);
            $table->dropColumn(['email', 'is_verified', 'verified_at']);
        });
    }
};
