<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Avatar extends Model
{
    protected $fillable = [
        'user_id',
        'emotional_state',
        'happiness_level',
        'streak_days',
        'last_state_update',
        'motivational_message',
    ];

    protected function casts(): array
    {
        return [
            'happiness_level' => 'integer',
            'streak_days' => 'integer',
            'last_state_update' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
