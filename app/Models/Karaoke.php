<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Karaoke extends Model
{
    protected $appends = ['is_online'];

    protected $fillable = [
        'user_id',
        'karaoke_id',
        'name',
        'status',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function unplayedSongs(){
        return $this->hasMany(Song::class)->where('status', 'unplayed');
    }

    public function playedSongs(){
        return $this->hasMany(Song::class)->where('status', 'played')->orderByDesc('created_at')->limit(20);
    }

    public function remote(){
        return $this->hasOne(User::class, 'karaoke_id', 'karaoke_id')->where('role', 'remote');
    }

    public function getIsOnlineAttribute()
    {
        if(!$this->last_seen_at) return false;

        // $diff = now()->diffInSeconds($this->last_seen_at);
        $diff = $this->last_seen_at->diffInSeconds(now());

        // Log::info($diff);

        return $diff < 90;
    }
}
