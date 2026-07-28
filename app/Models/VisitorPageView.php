<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorPageView extends Model
{
    protected $fillable = [
        'visitor_id',
        'url'
    ];

    public function visitor()
    {
        return $this->belongsTo(Visitor::class);
    }
}
