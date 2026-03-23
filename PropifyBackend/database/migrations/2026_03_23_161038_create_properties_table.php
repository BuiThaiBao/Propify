<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('type');
            $table->string('province_code', 20);
            $table->string('district_code', 20);
            $table->string('address_detail')->nullable();
            $table->decimal('area', 10, 2);
            $table->decimal('price', 15, 2);
            $table->integer('bedrooms')->default(0);
            $table->integer('bathrooms')->default(0);

            $table->decimal('lat', 10, 8);
            $table->decimal('lng', 11, 8);
            $table->timestamps();

            // Index cho Mapview search
            $table->index(['lat', 'lng'], 'idx_properties_location');
            $table->index('price', 'idx_properties_price');
        });
    }

    public function down()
    {
        Schema::dropIfExists('properties');
    }
};
