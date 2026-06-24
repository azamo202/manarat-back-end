<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('lesson_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category')->nullable();
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->unsignedTinyInteger('passing_score')->default(50)->comment('Percentage 0-100');
            $table->unsignedSmallInteger('time_limit_minutes')->nullable()->comment('NULL = unlimited');
            $table->unsignedTinyInteger('max_attempts')->default(1)->comment('0 = unlimited');
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('shuffle_answers')->default(false);
            $table->boolean('show_correct_answers')->default(false);
            $table->boolean('show_score_after_submit')->default(true);
            $table->timestamp('active_from')->nullable();
            $table->timestamp('active_until')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('lesson_id');
            $table->index('course_id');
            $table->index(['active_from', 'active_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
