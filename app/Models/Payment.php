<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'base_price',
        'amount',
        "days",
        'status',
        'reference_number',
    ];

    public function files()
    {
        return $this->morphMany(File::class, 'fileable')
            ->orderByRaw("
                CASE 
                    WHEN path LIKE '%.jpg' THEN 0
                    WHEN path LIKE '%.jpeg' THEN 0
                    WHEN path LIKE '%.png' THEN 0
                    WHEN path LIKE '%.gif' THEN 0
                    WHEN path LIKE '%.webp' THEN 0
                    ELSE 1
                END
            ")
            ->orderBy('created_at', 'desc'); // newest first within groups
    }

    public function user(){
        return $this->belongsTo(User::class);
    }
}
