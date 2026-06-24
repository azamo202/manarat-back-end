<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', [
                'multiple_choice',
                'multiple_select',
                'true_false',
                'short_text',
                'long_text',
                'matching',
                'ordering',
                'fill_in_blank',
                'image_based',
                'audio_based',
                'video_based',
            ]);
            $table->text('content');
            $table->text('explanation')->nullable();
            $table->text('hint')->nullable();
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->unsignedSmallInteger('points')->default(1);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('difficulty');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
