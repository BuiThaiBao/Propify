<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('appointment_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slot_id')->constrained('appointment_slots')->cascadeOnDelete();
            $table->foreignId('viewer_id')->constrained('users');
            $table->dateTime('meet_time')->comment('Ngày giờ cụ thể khách chọn');
            $table->string('full_name', 100);
            $table->string('phone_number', 20);
            $table->string('email', 100)->nullable();
            $table->string('note')->nullable();
            $table->boolean('is_deleted')->default(false)->comment('Xóa mềm');
            $table->enum('status', ['PENDING', 'APPROVED', 'CANCELLED'])->default('PENDING');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('appointment_bookings');
    }
};
