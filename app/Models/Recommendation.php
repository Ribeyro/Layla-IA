<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recommendation extends Model
{
    protected $fillable = [
        'user_id',
        'content',
        'type',
        'priority',
        'viewed',
        'applied',
        'viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'viewed' => 'boolean',
            'applied' => 'boolean',
            'viewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
