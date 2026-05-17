<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_attributes', function (Blueprint $table) {
            $table->boolean('is_visible')->default(true)->after('attribute_id');
            $table->unsignedInteger('display_order')->default(0)->after('is_visible');
            $table->boolean('is_featured')->default(false)->after('display_order');
        });
    }

    public function down(): void
    {
        Schema::table('property_attributes', function (Blueprint $table) {
            $table->dropColumn(['is_visible', 'display_order', 'is_featured']);
        });
    }
};
