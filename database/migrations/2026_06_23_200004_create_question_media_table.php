<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['image', 'audio', 'video']);
            $table->string('file_path');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable()->comment('Size in bytes');
            $table->string('alt_text')->nullable()->comment('Accessibility description');
            $table->timestamps();

            $table->index('question_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_media');
    }
};
