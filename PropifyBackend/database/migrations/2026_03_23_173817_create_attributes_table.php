<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('attribute_groups')->cascadeOnDelete();

            $table->string('name')->comment('Tên lựa chọn: Đông Tứ Trạch, Có nội thất, Có Wifi...');
            $table->string('icon')->nullable()->comment('Icon hiển thị (nếu có)');
            $table->integer('order_index')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('attributes');
    }
};
