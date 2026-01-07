<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AddonSetting extends Model
{
    protected $table = 'addon_settings';

    protected $fillable = [
        'id',
        'key_name',
        'live_values',
        'test_values',
        'settings_type',
        'mode',
        'is_active',
        'additional_data',
    ];

    protected $casts = [
        'live_values' => 'array',
        'test_values' => 'array',
        'additional_data' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get active SMS gateway
     */
    public static function getActiveSmsGateway()
    {
        return static::where('settings_type', 'sms_config')
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get active email configuration
     */
    public static function getActiveEmailConfig()
    {
        return static::where('settings_type', 'email_config')
            ->where('is_active', true)
            ->where('mode', config('app.env') === 'production' ? 'live' : 'test')
            ->first();
    }

    /**
     * Get values based on mode
     */
    public function getValues()
    {
        $mode = $this->mode === 'live' ? 'live_values' : 'test_values';
        return $this->$mode ?? [];
    }

    /**
     * Get specific value from settings
     */
    public function getValue($key, $default = null)
    {
        $values = $this->getValues();
        return $values[$key] ?? $default;
    }
}
