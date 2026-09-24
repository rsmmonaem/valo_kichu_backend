<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    protected $fillable = [
        'ip_address',
        'country',
        'city',
        'location',
        'last_visited_at',
        'fb_event_id',
        'fbp',
        'fbc',
        'device_type',
        'user_agent',
        'referrer',
    ];

    protected $casts = [
        'last_visited_at' => 'datetime',
    ];

    public function pageViews()
    {
        return $this->hasMany(VisitorPageView::class);
    }
}
