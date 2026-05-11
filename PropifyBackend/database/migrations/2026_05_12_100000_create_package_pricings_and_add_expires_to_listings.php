<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_pricings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
            $table->unsignedSmallInteger('duration_days')->comment('3, 7, 10, 15, 30');
            $table->decimal('price', 15, 2);
            $table->string('label', 50)->comment('3 ngày, 1 tuần, 10 ngày, 15 ngày, 1 tháng');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Mỗi package chỉ có 1 pricing cho mỗi duration
            $table->unique(['package_id', 'duration_days'], 'uq_package_duration');
        });

        Schema::table('listings', function (Blueprint $table) {
            $table->timestamp('package_expires_at')->nullable()->after('package_id');
            $table->index('package_expires_at', 'idx_listings_package_expires');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropIndex('idx_listings_package_expires');
            $table->dropColumn('package_expires_at');
        });

        Schema::dropIfExists('package_pricings');
    }
};
