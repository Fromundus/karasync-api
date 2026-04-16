<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        "name",
        "description",
        "code",
        "price",
        "days",
        "recommended",
        "bottom_description",
    ];
}
