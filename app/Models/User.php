<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'last_name',
        'email',
        'password',
        'birth_date',
        'university',
        'career',
        'cycle',
        'last_connection',
        'active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'birth_date' => 'date',
            'last_connection' => 'datetime',
            'active' => 'boolean',
        ];
    }

    public function avatar()
    {
        return $this->hasOne(Avatar::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function reminders()
    {
        return $this->hasMany(Reminder::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function dailyStatistics()
    {
        return $this->hasMany(DailyStatistic::class);
    }

    public function recommendations()
    {
        return $this->hasMany(Recommendation::class);
    }

    public function voiceSessions()
    {
        return $this->hasMany(VoiceSession::class);
    }

    public function habits()
    {
        return $this->hasMany(Habit::class);
    }
}
