<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('property_attributes', function (Blueprint $table) {
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained('attributes')->cascadeOnDelete();

            // Thiết lập Khóa chính kép (Composite Primary Key) để 1 căn nhà không lưu trùng 1 thuộc tính 2 lần
            $table->primary(['property_id', 'attribute_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('property_attributes');
    }
};
