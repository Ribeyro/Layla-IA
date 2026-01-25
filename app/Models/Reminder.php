<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reminder extends Model
{
    protected $fillable = [
        'user_id',
        'task_id',
        'reminder_datetime',
        'message',
        'type',
        'sent',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'reminder_datetime' => 'datetime',
            'sent' => 'boolean',
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
