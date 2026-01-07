<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessSetting extends Model
{
    use HasFactory;

    protected $table = 'business_settings';

    protected $fillable = [
        'key',
        'value',
        'type',
    ];

    // Don't cast value by default - handle it dynamically based on type

    // Type constants
    const TYPE_STRING = 'string';
    const TYPE_INTEGER = 'integer';
    const TYPE_FLOAT = 'float';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_ARRAY = 'array';
    const TYPE_JSON = 'json';

    public function languages()
    {
        return $this->hasMany(Language::class, 'business_setting_id');
    }

    public function currencies()
    {
        return $this->hasMany(Currency::class, 'business_setting_id');
    }

    public function appVersionControls()
    {
        return $this->hasMany(AppVersionControl::class, 'business_setting_id');
    }

    /**
     * Get a setting value by key
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        
        if (!$setting) {
            return $default;
        }

        return self::castValue($setting->value, $setting->type ?? self::TYPE_STRING);
    }

    /**
     * Set a setting value by key
     */
    public static function setValue(string $key, $value, string $type = null): void
    {
        if ($type === null) {
            $type = self::detectType($value);
        }

        self::updateOrCreate(
            ['key' => $key],
            [
                'value' => self::prepareValue($value, $type),
                'type' => $type,
            ]
        );
    }

    /**
     * Get all settings as key-value array
     */
    public static function getAllSettings(): array
    {
        $settings = self::all();
        $result = [];

        foreach ($settings as $setting) {
            $result[$setting->key] = self::castValue($setting->value, $setting->type ?? self::TYPE_STRING);
        }

        return $result;
    }

    /**
     * Detect the type of a value
     */
    protected static function detectType($value): string
    {
        if (is_bool($value)) {
            return self::TYPE_BOOLEAN;
        } elseif (is_int($value)) {
            return self::TYPE_INTEGER;
        } elseif (is_float($value)) {
            return self::TYPE_FLOAT;
        } elseif (is_array($value)) {
            return self::TYPE_ARRAY;
        } else {
            return self::TYPE_STRING;
        }
    }

    /**
     * Prepare value for storage
     */
    protected static function prepareValue($value, string $type)
    {
        switch ($type) {
            case self::TYPE_BOOLEAN:
                return json_encode((bool) $value);
            case self::TYPE_INTEGER:
                return (string) (int) $value;
            case self::TYPE_FLOAT:
                return (string) (float) $value;
            case self::TYPE_ARRAY:
            case self::TYPE_JSON:
                return json_encode(is_array($value) ? $value : json_decode($value, true));
            default:
                return (string) $value;
        }
    }

    /**
     * Cast value based on type
     */
    protected static function castValue($value, string $type)
    {
        if ($value === null) {
            return null;
        }

        switch ($type) {
            case self::TYPE_BOOLEAN:
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case self::TYPE_INTEGER:
                return (int) $value;
            case self::TYPE_FLOAT:
                return (float) $value;
            case self::TYPE_ARRAY:
            case self::TYPE_JSON:
                $decoded = json_decode($value, true);
                return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
            default:
                return (string) $value;
        }
    }
}
