<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('appointment_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->foreignId('poster_id')->constrained('users');
            $table->tinyInteger('day_of_week')->comment('1=T2, 2=T3, 3=T4, 4=T5, 5=T6, 6=T7, 7=CN');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true)->comment('false = xóa mềm');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('appointment_slots');
    }
};
