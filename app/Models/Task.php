<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'type',
        'start_date',
        'due_date',
        'priority',
        'status',
        'progress_percentage',
        'subject',
        'estimated_time_minutes',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'datetime',
            'completed_at' => 'datetime',
            'progress_percentage' => 'integer',
            'estimated_time_minutes' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
