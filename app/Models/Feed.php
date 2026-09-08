<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feed extends Model
{
    protected static function booted()
    {
        static::created(function (self $feed) {
            \App\Jobs\GenerateFeedJob::dispatch($feed);
        });
        static::updated(function (self $feed) {
            if ($feed->isDirty(['filters', 'field_mapping', 'schedule_cron', 'is_active'])) {
                \App\Jobs\GenerateFeedJob::dispatch($feed);
            }
        });
    }

    protected $fillable = [
        'name',
        'format',
        'filters',
        'field_mapping',
        'schedule_cron',
        'is_active',
        'last_generated_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'field_mapping' => 'array',
        'is_active' => 'boolean',
        'last_generated_at' => 'datetime',
    ];
}
?>
