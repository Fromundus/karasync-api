<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ReferenceService
{
    public static function generate(){
        $reference = strtoupper(Str::random(6));

        return $reference;
    }
}
