<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // EGG COLLECTIONS - Track daily collection
        Schema::create('egg_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_owner_id')->constrained('farm_owners')->onDelete('cascade');
            $table->foreignId('flock_id')->constrained('flocks')->onDelete('cascade');
            $table->date('collection_date');
            $table->integer('eggs_collected')->default(0);
            $table->integer('eggs_broken')->default(0);
            $table->integer('graded_a')->default(0);
            $table->integer('graded_b')->default(0);
            $table->integer('graded_c')->default(0);
            $table->string('batch_id')->unique();
            $table->timestamps();
            
            $table->index(['farm_owner_id', 'flock_id']);
            $table->index('collection_date');
        });

        // EGG INVENTORY - Multi-dimensional tracking
        Schema::create('egg_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_owner_id')->constrained('farm_owners')->onDelete('cascade');
            $table->foreignId('flock_id')->constrained('flocks')->onDelete('cascade');
            $table->foreignId('egg_collection_id')->nullable()->constrained('egg_collections')->onDelete('set null');
            
            // Multi-dimensional tracking
            $table->enum('egg_type', ['organic', 'white', 'brown'])->default('white');
            $table->enum('grade', ['A', 'B', 'C'])->default('A');
            $table->enum('size', ['large', 'small'])->default('large');
            
            // Quantities & tracking
            $table->integer('quantity_total')->default(0);
            $table->integer('quantity_available')->default(0); // Available - not reserved
            $table->integer('quantity_sold')->default(0);
            $table->integer('quantity_expired')->default(0);
            
            // Dates & Fresh Tracking
            $table->date('collection_date');
            $table->date('freshness_expires_at');
            $table->enum('status', ['fresh', 'expiring_soon', 'expired'])->default('fresh');
            $table->string('batch_id');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for fast queries
            $table->index(['farm_owner_id', 'status']);
            $table->index(['farm_owner_id', 'egg_type', 'grade', 'size']);
            $table->index('freshness_expires_at');
            $table->unique(['farm_owner_id', 'flock_id', 'egg_type', 'grade', 'size', 'collection_date'], 'unique_egg_entry');
        });

        // LIVESTOCK INVENTORY - For broiler, breeder, fighting cock, layers
        Schema::create('livestock_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_owner_id')->constrained('farm_owners')->onDelete('cascade');
            $table->foreignId('flock_id')->constrained('flocks')->onDelete('cascade');
            
            // Type of animal
            $table->enum('livestock_type', ['broiler', 'layer', 'breeder', 'fighting_cock', 'native', 'duck', 'quail'])->default('broiler');
            
            // Age & readiness for sale
            $table->integer('age_weeks')->default(0);
            $table->integer('weeks_until_ready')->default(0); // Auto-calculated: standard_age - age_weeks
            $table->date('estimated_ready_date')->nullable();
            
            // Quantities
            $table->integer('quantity_available_for_sale')->default(0);
            $table->integer('quantity_reserved')->default(0);
            $table->integer('quantity_sold')->default(0);
            
            // Physical attributes
            $table->decimal('average_weight_kg', 5, 2)->default(0);
            $table->enum('status', ['growing', 'ready_for_sale', 'partial_sale', 'sold_out'])->default('growing');
            
            // Cost tracking
            $table->decimal('acquisition_cost_per_unit', 10, 2)->default(0);
            $table->decimal('total_feed_cost', 15, 2)->default(0);
            $table->decimal('total_vaccine_cost', 15, 2)->default(0);
            
            // Sale info
            $table->date('ready_date')->nullable();
            $table->date('last_updated')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['farm_owner_id', 'livestock_type']);
            $table->index(['farm_owner_id', 'status']);
            $table->index('estimated_ready_date');
        });

        // INVENTORY TRANSACTIONS - Log all movements
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_owner_id')->constrained('farm_owners')->onDelete('cascade');
            
            // What moved
            $table->enum('inventory_type', ['egg', 'livestock', 'supply'])->default('egg');
            $table->nullableMorphs('inventoryable'); // Egg, Livestock, or Supply
            
            // Transaction details
            $table->enum('transaction_type', ['collection', 'sale', 'consumption', 'expiry', 'adjustment', 'loss'])->default('collection');
            $table->integer('quantity')->default(0);
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->decimal('total_amount', 15, 2)->nullable();
            
            // Reference info
            $table->string('reference_id')->nullable(); // Order ID, Supplier ID, etc
            $table->string('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamp('transaction_date')->useCurrent();
            $table->timestamps();
            
            $table->index(['farm_owner_id', 'inventory_type']);
            $table->index('transaction_type');
            $table->index('transaction_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('livestock_inventory');
        Schema::dropIfExists('egg_inventory');
        Schema::dropIfExists('egg_collections');
    }
};
