<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Song extends Model
{
    protected $fillable = [
        "karaoke_id",
        "code",
        "thumbnail",
        "title",
        "channel",
        "status",
        "color",
    ];

    public function karaoke(){
        return $this->belongsTo(Karaoke::class);
    }
}
