<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SongBook extends Model
{
    protected $fillable = [
        "code",
        "thumbnail",
        "title",
        "channel",
        "status",
        "color",
    ];
}