<?php

namespace App\Exceptions;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 🔥 BẮT BUỘC: disable FK trước
        Schema::disableForeignKeyConstraints();

        // 1. Drop bảng packages (không còn bị chặn)
        Schema::dropIfExists('packages');

        // 2. Tạo lại bảng
        Schema::create('packages', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('price', 10, 2)->default(0);

            $table->unsignedTinyInteger('priority')->default(1);
            $table->float('multiplier')->default(1.0);
            $table->unsignedInteger('daily_quota')->default(100);
            $table->float('decay_rate')->default(0.05);

            $table->string('badge')->nullable();
            $table->string('color')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });

        // 3. Enable lại FK
        Schema::enableForeignKeyConstraints();

        // 4. Gắn lại FK
        Schema::table('listings', function (Blueprint $table) {
            $table->foreign('package_id')
                ->references('id')
                ->on('packages')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('packages');

        Schema::enableForeignKeyConstraints();
    }
};
