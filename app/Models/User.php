<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\HasApiTokens;

use function Symfony\Component\Clock\now;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $appends = [
        'subscription_status',
    ];

    protected $fillable = [
        'karaoke_id',
        'name',
        'email',
        'password',
        'role',
        'provider',
        'provider_id',
        'avatar',
        'status',

        'karaoke_limit',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
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
        ];
    }

    public function karaokes(){
        return $this->hasMany(Karaoke::class)->orderByDesc('last_seen_at');
    }
    
    public function karaoke(){
        return $this->belongsTo(Karaoke::class, 'karaoke_id', 'karaoke_id');
    }

    public function getSubscriptionStatusAttribute(){
        if(!$this->expires_at) return true;

        $diff = $this->expires_at->diffInSeconds(now());

        // Log::info($diff);

        return $diff < 0;
    }

    public function pendingPayment(){
        return $this->hasOne(Payment::class)->where('status', 'pending');
    }
}
