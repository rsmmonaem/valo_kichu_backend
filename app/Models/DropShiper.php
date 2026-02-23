<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class DropShiper extends Model
{
    protected $fillable = [
        'customer_id',
        'name',
        'email',
        'phone',
        'is_active',
        'store_logo',
        'store_banner',
        'slogan',
        'about_us',
        'social_links',
    ];

    protected $casts = [
        'social_links' => 'json',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class,'customer_id');
    }
}
