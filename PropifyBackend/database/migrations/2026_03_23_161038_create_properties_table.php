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
            $table->string('type', 50)->comment('APARTMENT, HOUSE, LAND, OFFICE, ROOM...');
            $table->string('province_code', 20);
            $table->string('district_code', 20);
            $table->string('ward_code', 20)->nullable();
            $table->string('street_code', 20)->nullable();
            $table->string('project_name', 255)->nullable();
            $table->string('address_detail')->nullable();
            $table->decimal('area', 10, 2);
            $table->decimal('price', 15, 2);
            $table->boolean('is_negotiable')->default(false);
            $table->integer('bedrooms')->default(0);
            $table->integer('bathrooms')->default(0);
            $table->integer('floors')->nullable();
            $table->integer('floor_number')->nullable();
            $table->integer('balconies')->nullable();
            $table->decimal('facade_width', 6, 2)->nullable();
            $table->decimal('depth', 6, 2)->nullable();
            $table->decimal('road_width', 6, 2)->nullable();
            $table->string('direction_code', 30)->nullable();
            $table->string('balcony_direction_code', 30)->nullable();
            $table->string('furniture_status', 30)->nullable()->comment('NONE, BASIC, FULL');
            $table->text('contact_name')->nullable();
            $table->string('contact_phone', 20)->nullable();
            $table->string('contact_email', 255)->nullable();
            $table->string('poster_type', 20)->nullable()->comment('OWNER, BROKER');
            $table->json('meta')->nullable();

            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();
            $table->timestamps();

            // Index cho Mapview search
            $table->index(['lat', 'lng'], 'idx_properties_location');
            $table->index('price', 'idx_properties_price');
            $table->index(['province_code', 'district_code'], 'idx_properties_region');
            $table->index('type', 'idx_properties_type');
        });
    }

    public function down()
    {
        Schema::dropIfExists('properties');
    }
};
