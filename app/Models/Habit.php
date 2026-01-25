<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Habit extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'frequency',
        'preferred_time',
        'active',
        'current_streak',
        'max_streak',
    ];

    protected function casts(): array
    {
        return [
            'preferred_time' => 'datetime:H:i:s',
            'active' => 'boolean',
            'current_streak' => 'integer',
            'max_streak' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
