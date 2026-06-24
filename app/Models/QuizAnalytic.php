<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAnalytic extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'avg_score'            => 'decimal:2',
            'avg_duration_seconds' => 'decimal:2',
            'completion_rate'      => 'decimal:2',
            'pass_rate'            => 'decimal:2',
            'updated_at'           => 'datetime',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }
}
