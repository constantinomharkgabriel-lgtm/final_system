<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // For eggs - multi-dimensional tracking
            $table->string('egg_type')->nullable(); // 'organic', 'white', 'brown'
            $table->string('egg_grade')->nullable(); // 'A', 'B', 'C'
            $table->string('egg_size')->nullable(); // 'large', 'small'
            
            // For livestock - type tracking
            $table->string('livestock_type')->nullable(); // 'broiler', 'breeder', etc
            
            // Link to inventory model
            $table->unsignedBigInteger('egg_inventory_id')->nullable();
            $table->unsignedBigInteger('livestock_inventory_id')->nullable();
            
            // Inventory sync status
            $table->boolean('auto_sync_inventory')->default(true);
            
            // Foreign keys
            $table->foreign('egg_inventory_id')->references('id')->on('egg_inventory')->onDelete('set null');
            $table->foreign('livestock_inventory_id')->references('id')->on('livestock_inventory')->onDelete('set null');
            
            // Indexes
            $table->index(['egg_type', 'egg_grade', 'egg_size']);
            $table->index('livestock_type');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropMorphscolumns('inventoryable');
            $table->dropColumn([
                'egg_type', 'egg_grade', 'egg_size', 'livestock_type',
                'egg_inventory_id', 'livestock_inventory_id', 'auto_sync_inventory'
            ]);
        });
    }
};
