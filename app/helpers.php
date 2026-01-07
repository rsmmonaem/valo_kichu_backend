<?php

use App\Models\BusinessSetting as Setting;
use Illuminate\Support\Facades\DB;

if (!function_exists('getWebConfig')) {
    /**
     * Get a web configuration value from Setting or Settings model
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function getWebConfig(string $key, $default = null)
    {
        // First try Setting
        $value = Setting::getValue($key, null);
        
        if ($value !== null) {
            return $value;
        }
        
        // Then try Settings model (fallback)
        $settings = Setting::first();
        if ($settings && isset($settings->$key)) {
            return $settings->$key;
        }
        
        return $default;
    }
}

if (!function_exists('getAllBusinessSettings')) {
    /**
     * Get all business settings as an array
     * 
     * @return array
     */
    function getAllBusinessSettings(): array
    {
        return Setting::getAllSettings();
    }
}

if (!function_exists('getBusinessSetting')) {
    /**
     * Get a specific business setting value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function getBusinessSetting(string $key, $default = null)
    {
        return Setting::getValue($key, $default);
    }
}

if (!function_exists('setBusinessSetting')) {
    /**
     * Set a business setting value
     * 
     * @param string $key
     * @param mixed $value
     * @param string|null $type
     * @return void
     */
    function setBusinessSetting(string $key, $value, ?string $type = null): void
    {
        Setting::setValue($key, $value, $type);
    }
}

if (!function_exists('getBannerCount')) {
    /**
     * Get banner count safely
     */
    function getBannerCount(): int
    {
        try {
            // Try 'banner' table first, then fallback to 'banners'
            if (\Illuminate\Support\Facades\Schema::hasTable('banner')) {
                return \App\Models\Banner::count();
            } elseif (\Illuminate\Support\Facades\Schema::hasTable('banners')) {
                return DB::table('banners')->count();
            }
            return 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
}

if (!function_exists('getOrderCount')) {
    /**
     * Get order count safely
     */
    function getOrderCount(?string $status = null): int
    {
        try {
            $query = \App\Models\Order::query();
            if ($status) {
                $query->where('status', $status);
            }
            return $query->count();
        } catch (\Exception $e) {
            return 0;
        }
    }
}

if (!function_exists('getMessageCount')) {
    /**
     * Get message count safely
     */
    function getMessageCount(): int
    {
        try {
            return \App\Models\ContactMessage::count();
        } catch (\Exception $e) {
            return 0;
        }
    }
}

if (!function_exists('getModelCount')) {
    /**
     * Safely get model count with error handling
     * 
     * @param string $modelClass
     * @param string|null $tableName
     * @param array $conditions
     * @return int
     */
    function getModelCount(string $modelClass, ?string $tableName = null, array $conditions = []): int
    {
        try {
            if (!class_exists($modelClass)) {
                return 0;
            }
            
            $query = $modelClass::query();
            
            foreach ($conditions as $column => $value) {    
                $query->where($column, $value);
            }
            
            return $query->count();
        } catch (\Exception $e) {
            return 0;
        }
    }
}

if (!function_exists('getCurrency')) {
    /**
     * Get currency by code or return default currency
     * 
     * @param string|null $currencyCode
     * @return \App\Models\Currency|null
     */
    function getCurrency(?string $currencyCode = null)
    {
        try {
            if ($currencyCode) {
                $currency = \App\Models\Currency::where('currency_code', $currencyCode)->first();
                if ($currency) {
                    return $currency;
                }
            }
            
            // Return default currency if no code provided or code not found
            return \App\Models\Currency::where('is_default', true)->first();
        } catch (\Exception $e) {
            return null;
        }
    }
}

if (!function_exists('convertCurrency')) {
    /**
     * Convert amount to currency format
     * 
     * @param float $amount
     * @param string|null $currencyCode
     * @return string
     */
    function convertCurrency($amount, ?string $currencyCode = null)
    {
        try {
            $currency = getCurrency($currencyCode);
            $defaultCurrency = getCurrency();
            if (!$currency) {
                return number_format($amount, 2);
            }
            
            $convertedAmount = $amount * ($currency->exchange_rate ?? 1) / ($defaultCurrency->exchange_rate ?? 1);
            return $currency->symbol . ' ' . number_format($convertedAmount, 2);
        } catch (\Exception $e) {
            return number_format($amount, 2);
        }
    }
}

if (!function_exists('setCurrency')) {
    /**
     * Set currency (for session/cookie storage)
     * 
     * @param string $currencyCode
     * @return bool
     */
    function setCurrency(string $currencyCode): bool
    {
        try {
            $currency = \App\Models\Currency::where('currency_code', $currencyCode)->first();
            if ($currency) {
                session(['currency' => $currencyCode]);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
}

if (!function_exists('getCurrencyRate')) {
    /**
     * Get exchange rate for a currency
     * 
     * @param string|null $currencyCode
     * @return float
     */
    function getCurrencyRate(?string $currencyCode = null): float
    {
        try {
            $currency = getCurrency($currencyCode);
            return $currency ? ($currency->exchange_rate ?? 1.0) : 1.0;
        } catch (\Exception $e) {
            return 1.0;
        }
    }
}
