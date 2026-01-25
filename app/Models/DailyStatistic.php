<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyStatistic extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'completed_tasks',
        'pending_tasks',
        'overdue_tasks',
        'study_minutes',
        'ai_interactions',
        'completion_percentage',
        'stress_level',
        'motivation_level',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'completed_tasks' => 'integer',
            'pending_tasks' => 'integer',
            'overdue_tasks' => 'integer',
            'study_minutes' => 'integer',
            'ai_interactions' => 'integer',
            'completion_percentage' => 'decimal:2',
            'stress_level' => 'integer',
            'motivation_level' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
