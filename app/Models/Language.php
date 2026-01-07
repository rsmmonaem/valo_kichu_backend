<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    use HasFactory;

    protected $table = 'languages';

    protected $fillable = [
        'business_setting_id',
        'language_code',
        'name',
        'image',
        'status',
        'default',
        'direction',
    ];

    protected $casts = [
        'status' => 'boolean',
        'default' => 'boolean',
    ];

    public function businessSetting()
    {
        return $this->belongsTo(BusinessSetting::class, 'business_setting_id');
    }
}
