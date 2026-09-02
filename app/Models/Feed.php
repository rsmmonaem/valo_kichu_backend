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
            if ($feed->isDirty(['field_mapping', 'schedule_cron', 'is_active'])) {
                \App\Jobs\GenerateFeedJob::dispatch($feed);
            }
        });
    }
    protected $fillable = [
        'name',
        'format',
        'field_mapping',
        'schedule_cron',
        'is_active',
        'last_generated_at',
    ];

    protected $casts = [
        'field_mapping' => 'array',
        'is_active' => 'boolean',
        'last_generated_at' => 'datetime',
    ];
}
?>
