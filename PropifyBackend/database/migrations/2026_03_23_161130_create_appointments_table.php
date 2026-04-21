<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            // Đặt tên khóa ngoại cụ thể vì trỏ về cùng 1 bảng users
            $table->foreignId('viewer_id')->nullable()->constrained('users');
            $table->foreignId('poster_id')->constrained('users');

            $table->dateTime('meet_time');
            $table->string('contact_name', 100)->nullable();
            $table->string('contact_phone', 20)->nullable();
            $table->string('contact_email', 255)->nullable();
            $table->string('note')->nullable();
            $table->string('status')->default('PENDING')->comment('PENDING, CONFIRMED, DECLINED, CANCELLED, COMPLETED');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('appointments');
    }
};
