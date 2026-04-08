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
            // Add columns for PayMongo integration
            $table->string('paymongo_session_id')->nullable()->after('disbursement_reference');
            $table->string('paymongo_payment_intent_id')->nullable()->after('paymongo_session_id');
            $table->json('payment_details')->nullable()->after('paymongo_payment_intent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll', function (Blueprint $table) {
            $table->dropColumn(['paymongo_session_id', 'paymongo_payment_intent_id', 'payment_details']);
        });
    }
};
