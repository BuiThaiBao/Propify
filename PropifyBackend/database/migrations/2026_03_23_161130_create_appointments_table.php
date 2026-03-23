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
            $table->foreignId('viewer_id')->constrained('users');
            $table->foreignId('poster_id')->constrained('users');

            $table->dateTime('meet_time');
            $table->string('note')->nullable();
            $table->string('status')->default('PENDING')->comment('PENDING, APPROVED, CANCELLED');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('appointments');
    }
};
