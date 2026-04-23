<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('properties')) {
            return; // Bảng chưa tồn tại, bỏ qua
        }

        Schema::table('properties', function (Blueprint $table) {
            if (!Schema::hasColumn('properties', 'street_code')) {
                $table->string('street_code', 255)->nullable();
            } else {
                $table->string('street_code', 255)->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('properties') || !Schema::hasColumn('properties', 'street_code')) {
            return; // Bảng hoặc cột không tồn tại, bỏ qua
        }

        Schema::table('properties', function (Blueprint $table) {
            $table->string('street_code', 20)->nullable()->change();
        });
    }
};
