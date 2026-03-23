<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('user_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->string('type')->default('FAVORITE')->comment('FAVORITE, VIEWED');
            $table->timestamps();

            // Đảm bảo không bị duplicate dữ liệu (1 user không thể thả tim 2 lần cho 1 tin)
            $table->unique(['user_id', 'listing_id', 'type'], 'unique_user_listing');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_favorites');
    }
};
