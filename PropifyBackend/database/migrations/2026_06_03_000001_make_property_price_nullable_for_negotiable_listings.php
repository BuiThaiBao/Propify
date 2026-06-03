<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->decimal('price', 15, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('properties')->whereNull('price')->update(['price' => 0]);

        Schema::table('properties', function (Blueprint $table) {
            $table->decimal('price', 15, 2)->nullable(false)->default(0)->change();
        });
    }
};
