<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_duration_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('days')->unique();
            $table->string('label', 50);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        DB::table('package_duration_options')->insert([
            ['days' => 3, 'label' => '3 ngày', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['days' => 5, 'label' => '5 ngày', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['days' => 7, 'label' => '7 ngày', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['days' => 10, 'label' => '10 ngày', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['days' => 15, 'label' => '15 ngày', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['days' => 30, 'label' => '30 ngày', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('package_duration_options');
    }
};
