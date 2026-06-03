<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tạo bảng transaction_notes
        Schema::create('transaction_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->onDelete('cascade');
            $table->foreignId('admin_id')->constrained('users');
            $table->text('note');
            $table->timestamp('created_at')->useCurrent();
        });

        // 2. Thêm indexes tối ưu hiệu năng cho transactions
        Schema::table('transactions', function (Blueprint $table) {
            $table->index('status');
            $table->index('payment_method');
            $table->index('package_id');
            $table->index('transaction_date');
            $table->index('amount');
            $table->index('vnp_txn_ref');
            $table->index('vnp_transaction_no');
            $table->index(['status', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_notes');

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['payment_method']);
            $table->dropIndex(['package_id']);
            $table->dropIndex(['transaction_date']);
            $table->dropIndex(['amount']);
            $table->dropIndex(['vnp_txn_ref']);
            $table->dropIndex(['vnp_transaction_no']);
            $table->dropIndex(['status', 'transaction_date']);
        });
    }
};
