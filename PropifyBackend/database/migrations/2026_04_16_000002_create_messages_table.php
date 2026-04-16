<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('sender_id');

            // Hỗ trợ nhiều loại message (text, image, file)
            $table->enum('type', ['text', 'image', 'file'])->default('text');
            $table->text('body')->nullable();       // null khi type = image/file
            $table->string('file_url')->nullable(); // url ảnh/file

            // last_read optimization
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();

            // Foreign keys
            $table->foreign('conversation_id')->references('id')->on('conversations')->cascadeOnDelete();
            $table->foreign('sender_id')->references('id')->on('users')->cascadeOnDelete();

            // Index để query messages của conversation (pagination, latest first)
            $table->index(['conversation_id', 'created_at']);
            $table->index('sender_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
