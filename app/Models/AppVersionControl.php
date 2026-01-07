<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppVersionControl extends Model
{
    protected $fillable = [
        'app',
        'device',
        'version',
        'link',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'version' => 'string',
    ];
}
