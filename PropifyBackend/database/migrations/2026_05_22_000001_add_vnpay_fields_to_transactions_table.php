<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedInteger('duration_days')->nullable()->after('amount');
            $table->string('vnp_txn_ref', 50)->nullable()->after('transaction_date');
            $table->string('vnp_transaction_no', 50)->nullable()->after('vnp_txn_ref');
            $table->string('vnp_bank_code', 50)->nullable()->after('vnp_transaction_no');
            $table->string('vnp_response_code', 10)->nullable()->after('vnp_bank_code');
            $table->string('vnp_pay_date', 20)->nullable()->after('vnp_response_code');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'duration_days',
                'vnp_txn_ref',
                'vnp_transaction_no',
                'vnp_bank_code',
                'vnp_response_code',
                'vnp_pay_date',
            ]);
        });
    }
};
