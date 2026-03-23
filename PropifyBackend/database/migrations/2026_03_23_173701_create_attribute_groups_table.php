<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('attribute_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Tên nhóm: Hướng nhà, Nội thất, Tiện ích, Pháp lý...');
            $table->string('code', 50)->unique()->comment('Mã code chuẩn: direction, furniture, amenities...');
            $table->string('input_type', 20)->default('checkbox')->comment('radio, checkbox, select (Dùng cho FE vẽ UI)');
            $table->integer('order_index')->default(0)->comment('Thứ tự hiển thị trên bộ lọc UI');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('attribute_groups');
    }
};
