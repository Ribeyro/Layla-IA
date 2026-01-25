<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoiceSession extends Model
{
    protected $fillable = [
        'user_id',
        'start_datetime',
        'end_datetime',
        'duration_seconds',
        'command_count',
        'successful',
        'transcription',
        'ai_response',
    ];

    protected function casts(): array
    {
        return [
            'start_datetime' => 'datetime',
            'end_datetime' => 'datetime',
            'duration_seconds' => 'integer',
            'command_count' => 'integer',
            'successful' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
