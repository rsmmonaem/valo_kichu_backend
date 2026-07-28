<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    protected $fillable = [
        'ip_address',
        'country',
        'city',
        'location'
    ];

    public function pageViews()
    {
        return $this->hasMany(VisitorPageView::class);
    }
}
