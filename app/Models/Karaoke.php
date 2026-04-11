<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Karaoke extends Model
{
    protected $fillable = [
        'user_id',
        'karaoke_id',
        'name',
        'status',
        'connection_token',
        'token_expires_at',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function unplayedSongs(){
        return $this->hasMany(Song::class)->where('status', 'unplayed');
    }
}
