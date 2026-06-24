<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attempt_id')->unique()->constrained('quiz_attempts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('raw_score')->default(0);
            $table->unsignedSmallInteger('max_score')->default(0);
            $table->unsignedSmallInteger('final_score')->default(0);
            $table->decimal('percentage', 5, 2)->default(0.00);
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->boolean('passed')->default(false);
            $table->boolean('certificate_eligible')->default(false);
            $table->unsignedTinyInteger('attempt_number')->default(1);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['quiz_id', 'user_id']);
            $table->index(['quiz_id', 'passed']);
            $table->index(['quiz_id', 'percentage']);
            $table->index(['user_id', 'passed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_results');
    }
};
