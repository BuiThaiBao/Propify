<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('listing_id')->constrained('listings');
            $table->foreignId('package_id')->constrained('packages');
            $table->decimal('amount', 15, 2);
            $table->string('payment_method', 50)->nullable()->comment('VNPAY, MOMO...');
            $table->string('status')->default('PENDING')->comment('PENDING, SUCCESS, FAILED');
            $table->timestamp('transaction_date')->useCurrent();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
};