<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SearchSynonym extends Model
{
    protected $table = 'search_synonyms';

    protected $fillable = [
        'title',
        'terms',
        'is_active',
    ];

    protected $casts = [
        'terms' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::saved(function () {
            Cache::forget('search_synonyms_active_groups');
        });

        static::deleted(function () {
            Cache::forget('search_synonyms_active_groups');
        });
    }
}
