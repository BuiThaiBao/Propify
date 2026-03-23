<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('listing_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->string('image_url', 500);
            $table->boolean('is_thumbnail')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('listing_images');
    }
};
