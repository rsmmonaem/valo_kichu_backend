<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\Page;
use App\Models\Currency;
use App\Models\AppVersionControl;
use App\Models\PaymentGateway;
use App\Models\Division;
use App\Models\District;
use App\Models\City;
use Illuminate\Support\Facades\Schema;
use App\Models\Language;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    public function appConfig(Request $request)
    {
        // Get business_setting_id from a setting or create default
        $businessSettingId = BusinessSetting::getValue('business_setting_id', 1);
        
        // If no business_setting_id exists, create a default BusinessSetting record
        if (!BusinessSetting::where('key', 'business_setting_id')->exists()) {
            BusinessSetting::setValue('business_setting_id', 1, BusinessSetting::TYPE_INTEGER);
            $businessSettingId = 1;
        }


        $currencies = Currency::get();
        $defaultCurrency = Currency::where('is_default', true)->first();

        // App version controls
        $userAppVersions = [];
        $sellerAppVersions = [];
        $deliveryAppVersions = [];

        foreach (['android', 'ios'] as $device) {
            $userAppVersions["for_{$device}"] = AppVersionControl::where('app', 'user_app')
                ->where('device', $device)
                ->first();
            $sellerAppVersions["for_{$device}"] = AppVersionControl::where('app', 'seller_app')
                ->where('device', $device)
                ->first();
            $deliveryAppVersions["for_{$device}"] = AppVersionControl::where('app', 'delivery_app')
                ->where('device', $device)
                ->first();
        }

        // Payment methods - Initialize array
        $paymentMethods = [];
        $onlinePaymentData = null;
        $offlinePaymentData = null;
        // 1. Cash on Delivery - Check from business_settings
        $cashOnDelivery = BusinessSetting::getValue('cash_on_delivery', true);
        if ($cashOnDelivery) {
            $paymentMethods[] = [
                'type' => 'cod',
                'key_name' => 'cash_on_delivery',
                'additional_datas' => [
                    'gateway_title' => 'Cash on Delivery',
                    'gateway_image' => null
                ]
            ];
        }
        
        // 2. Offline Payment Methods - Check if table exists first
        if (Schema::hasTable('offline_payment_methods')) {
            try {
                $offlinePayments = \App\Models\OfflinePaymentMethod::where('status', 1)->get();
                if ($offlinePayments->count() > 0) {
                    $onlinePaymentData = [
                        'name' => 'Offline Payment',
                        'image' => null,
                    ];
                }
                foreach ($offlinePayments as $offlinePayment) {
                    $methodName = $offlinePayment->method_name ?? 'Offline Payment';
                    
                    // Extract gateway_title and gateway_image from live_values if available in payment_gateways
                    // For now, use method_name as gateway_title
                    $gatewayTitle = $methodName;
                    $gatewayImage = null;
                    
                    // Add to payment methods list
                    $paymentMethods[] = [
                        'type' => 'offline',
                        'key_name' => strtolower(str_replace(' ', '_', $methodName)), // e.g., 'bkash', 'nagad', 'rocket'
                        'additional_datas' => [
                            'gateway_title' => $gatewayTitle,
                            'gateway_image' => $gatewayImage,
                            'enable' => true,
                            'method_fields' => $offlinePayment->method_fields ?? null,
                            'method_informations' => $offlinePayment->method_informations ?? null,
                        ]
                    ];
                }
            } catch (\Exception $e) {
                // Table exists but query failed, skip offline payment
                // Log error if needed: \Log::error('Offline payment query failed: ' . $e->getMessage());
            }
        }

        // 3. Online Payment Gateways (PaymentGateway model)
        try {
            if (Schema::hasTable('payment_gateways')) {
                $gatewayConfigs = PaymentGateway::where('is_active', true)->get();

                if ($gatewayConfigs->count() > 0) {
                    $onlinePaymentData = [
                        'name' => 'Online Payment',
                        'image' => null,
                    ];
                }

                foreach ($gatewayConfigs as $gateway) {
                    $gatewayName = $gateway->key ?? 'unknown';
                    
                    // Extract gateway_title and gateway_image from live_values or test_values
                    $gatewayTitle = $gatewayName;
                    $gatewayImage = null;
                    
                    $values = $gateway->live_values ?? $gateway->test_values ?? [];
                    if (is_array($values)) {
                        $gatewayTitle = $values['gateway_title'] ?? $values['gateway'] ?? $gatewayName;
                        $gatewayImage = $values['gateway_image'] ?? null;
                    }
                    
                    $paymentMethods[] = [
                        'type' => 'online',
                        'key_name' => $gatewayName,
                        'additional_datas' => [
                            'gateway_title' => $gatewayTitle,
                            'gateway_image' => $gatewayImage ? asset('storage/' . $gatewayImage) : null,
                        ]
                    ];
                }
            }
        } catch (\Exception $e) {
            // PaymentGateway table doesn't exist or query failed
            // Continue without online payment gateways
        }

        // Languages
        $languages = Language::where('business_setting_id', $businessSettingId)
            ->where('status', true)
            ->get();

        // Determine isLtr
        $langCode = $request->header('Language');
        if (!$langCode) {
            $defaultLang = Language::where('business_setting_id', $businessSettingId)
                ->where('default', true)
                ->first();
            $langCode = $defaultLang ? $defaultLang->language_code : 'en';
        }

        $langObj = Language::where('business_setting_id', $businessSettingId)
            ->where('language_code', $langCode)
            ->first();
        $isLtr = $langObj ? ($langObj->direction === 'ltr') : true;

        // Get pages
        $termsAndConditions = Page::getTermsAndConditions();
        $privacyPolicy = Page::getPrivacyPolicy();
        $aboutUs = Page::getAboutUs();

        return response()->json([
            'id' => $businessSettingId,
            'shipping_fee' => BusinessSetting::getValue('shipping_fee', 0),
            'tax' => BusinessSetting::getValue('tax', 0),
            'currency_decimal_point_setting' => BusinessSetting::getValue('currency_decimal_point_setting', 2),
            'currency_symbol_position' => BusinessSetting::getValue('currency_symbol_position', 'left'),
            'business_name' => BusinessSetting::getValue('business_name', 'Tradlink'),
            'software_type' => 'single_vendor',
            'app_logo' => BusinessSetting::getValue('app_logo', null),
            'app_name' => BusinessSetting::getValue('app_name', null),
            'terms_and_conditions' => $termsAndConditions?->content,
            'privacy_policy' => $privacyPolicy?->content,
            'about_us' => $aboutUs?->content,
            'delivery_type' => BusinessSetting::getValue('delivery_type') ?? null,
            'delivery_method' => BusinessSetting::getValue('delivery_method') ?? null,
            'currency_type' => 'single',
            "is_verify_required" => (bool) BusinessSetting::getValue('is_verify_required'),
            "number_verify_required" => (bool) BusinessSetting::getValue('number_verify_required'),
            "email_verify_required" => (bool) BusinessSetting::getValue('email_verify_required'),
            'currency_list' => $currencies,
            'system_default_currency' => $defaultCurrency,
            'user_app_version_control' => $userAppVersions,
            'offline_payment' => $offlinePaymentData,
            'online_payment' => $onlinePaymentData,
            'cash_on_delivery' => $cashOnDelivery,
            'payment_methods' => $paymentMethods,
            'languages' => $languages,
            'isLtr' => $isLtr,
        ]);
    }

    /**
     * Get list of divisions
     * Includes nested districts and cities
     */
    public function divisionList(Request $request)
    {
        $divisions = Division::with(['districts.cities', 'districts'])
            ->orderBy('name', 'asc')
            ->get();
        
        return response()->json($divisions, 200);
    }

    /**
     * Get list of districts
     * Optional: Filter by division_id
     * Includes nested cities and division
     */
    public function districtList(Request $request)
    {
        $query = District::query();
        
        // Filter by division_id if provided
        if ($request->has('division_id')) {
            $query->where('division_id', $request->division_id);
        }
        
        $districts = $query->with(['cities', 'division'])
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $districts,
            'count' => $districts->count(),
        ], 200);
    }

    /**
     * Get list of cities
     * Optional: Filter by district_id or division_id
     * Includes nested district and division
     */
    public function cityList(Request $request)
    {
        $query = City::query();
        
        // Filter by district_id if provided
        if ($request->has('district_id')) {
            $query->where('district_id', $request->district_id);
        }
        
        // Filter by division_id if provided (through district relationship)
        if ($request->has('division_id')) {
            $query->whereHas('district', function($q) use ($request) {
                $q->where('division_id', $request->division_id);
            });
        }
        
        $cities = $query->with(['district.division'])
            ->orderBy('name', 'asc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $cities,
            'count' => $cities->count(),
        ], 200);
    }
}
