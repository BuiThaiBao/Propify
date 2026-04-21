<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('listing_verification_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->string('document_type', 30)->comment('ID_FRONT, ID_BACK, LEGAL_DOCUMENT');
            $table->string('file_url', 500);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['listing_id', 'document_type'], 'idx_listing_verification_docs');
        });
    }

    public function down()
    {
        Schema::dropIfExists('listing_verification_documents');
    }
};
