<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farm_owners', function (Blueprint $table) {
            // Add missing document columns if they don't exist
            if (!Schema::hasColumn('farm_owners', 'business_permit_path')) {
                $table->string('business_permit_path')->nullable()->after('valid_id_path');
            }
            if (!Schema::hasColumn('farm_owners', 'barangay_clearance_path')) {
                $table->string('barangay_clearance_path')->nullable()->after('business_permit_path');
            }
            if (!Schema::hasColumn('farm_owners', 'mayor_bir_registration_path')) {
                $table->string('mayor_bir_registration_path')->nullable()->after('barangay_clearance_path');
            }
            if (!Schema::hasColumn('farm_owners', 'ecc_certificate_path')) {
                $table->string('ecc_certificate_path')->nullable()->after('mayor_bir_registration_path');
            }
            if (!Schema::hasColumn('farm_owners', 'bai_registration_path')) {
                $table->string('bai_registration_path')->nullable()->after('ecc_certificate_path');
            }
            if (!Schema::hasColumn('farm_owners', 'locational_clearance_path')) {
                $table->string('locational_clearance_path')->nullable()->after('bai_registration_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('farm_owners', function (Blueprint $table) {
            $table->dropColumn([
                'business_permit_path',
                'barangay_clearance_path',
                'mayor_bir_registration_path',
                'ecc_certificate_path',
                'bai_registration_path',
                'locational_clearance_path',
            ]);
        });
    }
};
