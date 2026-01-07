<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = [
        'image_url', 
        'link', 
        'title', 
        'subtitle', 
        'order_index', 
        'is_active'
    ];
}
