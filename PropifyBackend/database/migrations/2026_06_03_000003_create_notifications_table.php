<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                if (! Schema::hasColumn('notifications', 'user_id')) {
                    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                }

                if (! Schema::hasColumn('notifications', 'type')) {
                    $table->string('type')->default('general');
                }

                if (! Schema::hasColumn('notifications', 'title')) {
                    $table->string('title')->default('');
                }

                if (! Schema::hasColumn('notifications', 'content')) {
                    $table->text('content')->nullable();
                }

                if (! Schema::hasColumn('notifications', 'data')) {
                    $table->json('data')->nullable();
                }

                if (! Schema::hasColumn('notifications', 'read_at')) {
                    $table->timestamp('read_at')->nullable();
                }

                if (! Schema::hasColumn('notifications', 'created_at')) {
                    $table->timestamps();
                }
            });

            return;
        }

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('title');
            $table->text('content');
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index('read_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
