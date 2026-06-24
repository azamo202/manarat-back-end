<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('quiz_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            // JSON column handles all answer types:
            // MC/TF: {"selected_option_id": 3}
            // Multiple Select: {"selected_option_ids": [2, 4]}
            // Short/Long text: {"text": "..."}
            // Matching: {"pairs": [{"left_id": 1, "right_id": 4}]}
            // Ordering: {"order": [3, 1, 4, 2]}
            // Fill-in-blank: {"answers": ["word1", "word2"]}
            $table->json('answer_value')->nullable();
            $table->boolean('is_correct')->nullable()->comment('NULL = not yet graded / open text');
            $table->unsignedSmallInteger('points_earned')->default(0);
            $table->unsignedSmallInteger('time_spent_seconds')->default(0);
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();

            $table->unique(['attempt_id', 'question_id']);
            $table->index(['attempt_id', 'is_correct']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_answers');
    }
};
