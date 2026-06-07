<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpLog extends Model
{
    protected $fillable = [
        'ip_address',
        'request_count',
        'last_request_at',
        'is_banned',
        'ban_reason'
    ];

    protected $casts = [
        'last_request_at' => 'datetime',
        'is_banned' => 'boolean'
    ];
}
