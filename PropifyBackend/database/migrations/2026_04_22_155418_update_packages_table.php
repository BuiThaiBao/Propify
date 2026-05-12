<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('packages');
        Schema::enableForeignKeyConstraints();

        Schema::create('packages', function (Blueprint $table) {
            $table->id();

            // Thông tin cơ bản
            $table->string('name'); // Gold, Silver
            $table->string('slug')->unique(); // gold, silver
            $table->decimal('price', 10, 2)->default(0);

            // Ranking core
            $table->unsignedTinyInteger('priority')->default(1);
            // 3 = gold, 2 = silver, 1 = normal

            $table->float('multiplier')->default(1.0);
            // boost score (1.5, 1.2...)

            $table->unsignedInteger('daily_quota')->default(100);
            // số lượt hiển thị/ngày

            $table->float('decay_rate')->default(0.05);
            // tốc độ tụt (càng lớn tụt càng nhanh)

            // UI
            $table->string('badge')->nullable(); // VIP, HOT
            $table->string('color')->nullable(); // vàng, bạc

            // Trạng thái
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Index quan trọng
            $table->index(['priority', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('packages');
        Schema::enableForeignKeyConstraints();
    }
};
