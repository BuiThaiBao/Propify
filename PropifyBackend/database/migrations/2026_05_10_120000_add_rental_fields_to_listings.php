<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            if (! Schema::hasColumn('listings', 'rent_min_term')) {
                $table->string('rent_min_term', 50)->nullable()->after('description')->comment('Enum: 1_month, 3_months, 6_months, 1_year');
            }
            if (! Schema::hasColumn('listings', 'rent_payment_interval')) {
                $table->string('rent_payment_interval', 50)->nullable()->after('rent_min_term')->comment('Enum: monthly, quarter, half_year, yearly');
            }
            if (! Schema::hasColumn('listings', 'rent_deposit')) {
                $table->string('rent_deposit', 50)->nullable()->after('rent_payment_interval')->comment('Enum: none, 1_month, 3_months, 6_months, 1_year');
            }
        });
    }

    public function down(): void
    {
        // additive-only migration: do not drop columns on rollback
    }
};
