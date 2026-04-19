<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $fillable = [
        'filename', 
        'path', 
        'mime_type', 
        'size',
        'type'
    ];

    protected $hidden = ['path', 'updated_at', 'created_at'];

    public function fileable() {
        return $this->morphTo();
    }

    protected $appends = ['url']; // auto include in JSON

    public function getUrlAttribute()
    {
        return env('APP_URL') . '/api/files/' . $this->path;
    }
}
