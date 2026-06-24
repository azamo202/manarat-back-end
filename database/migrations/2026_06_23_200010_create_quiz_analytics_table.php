<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('total_attempts')->default(0);
            $table->unsignedInteger('completed_attempts')->default(0);
            $table->unsignedInteger('pass_count')->default(0);
            $table->unsignedInteger('fail_count')->default(0);
            $table->decimal('avg_score', 5, 2)->default(0.00);
            $table->decimal('avg_duration_seconds', 10, 2)->default(0.00);
            $table->decimal('completion_rate', 5, 2)->default(0.00);
            $table->decimal('pass_rate', 5, 2)->default(0.00);
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_analytics');
    }
};
