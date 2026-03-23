<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('password')->nullable();
            $table->string('full_name', 100)->nullable();
            $table->string('phone', 20)->unique()->nullable();
            $table->string('email', 100)->unique()->nullable();
            $table->enum('role', ['USER', 'ADMIN'])->default('USER');
            $table->enum('status', ['A', 'IA', 'BAN'])->default('A');
            $table->timestamps(); // Tự động tạo created_at và updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};