<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorPageView extends Model
{
    protected $fillable = [
        'visitor_id',
        'url',
        'fb_event_id',
    ];

    public function visitor()
    {
        return $this->belongsTo(Visitor::class);
    }
}
