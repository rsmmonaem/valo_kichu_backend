<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BusinessSetting;

class BusinessSettingSeeder extends Seeder
{
    public function run(): void
    {
        // Set business_setting_id first
        BusinessSetting::setValue('business_setting_id', 1, BusinessSetting::TYPE_INTEGER);
        
        // Maintenance Mode
        BusinessSetting::setValue('maintenance_mode', false, BusinessSetting::TYPE_BOOLEAN);
        
        // Company Information
        BusinessSetting::setValue('company_name', 'Ecommatrix', BusinessSetting::TYPE_STRING);
        BusinessSetting::setValue('company_phone', '+8801712643138', BusinessSetting::TYPE_STRING);
        BusinessSetting::setValue('company_email', 'info@nibizsoft.com', BusinessSetting::TYPE_STRING);
        BusinessSetting::setValue('company_country', 'Bangladesh', BusinessSetting::TYPE_STRING);
        BusinessSetting::setValue('company_timezone', 'Asia/Dhaka', BusinessSetting::TYPE_STRING);
        BusinessSetting::setValue('company_language', 'english', BusinessSetting::TYPE_STRING);
        BusinessSetting::setValue('company_address', '38/3 Hafiz Uddin Sarkar Road, Dattapara, Tongi, Gazipur-1712 Bangladesh.', BusinessSetting::TYPE_STRING);
        BusinessSetting::setValue('company_latitude', '23.868694', BusinessSetting::TYPE_STRING);
        BusinessSetting::setValue('company_longitude', '90.369855', BusinessSetting::TYPE_STRING);
        
        // Business Information
        BusinessSetting::setValue('currency', 'BDT', BusinessSetting::TYPE_STRING);
        BusinessSetting::setValue('currency_symbol_position', 'right', BusinessSetting::TYPE_STRING);
        BusinessSetting::setValue('software_type', 'multi_vendor', BusinessSetting::TYPE_STRING);
        BusinessSetting::setValue('forgot_password_verification_by', 'email', BusinessSetting::TYPE_STRING);
        BusinessSetting::setValue('email_verification', true, BusinessSetting::TYPE_BOOLEAN);
        BusinessSetting::setValue('otp_verification', false, BusinessSetting::TYPE_BOOLEAN);
        BusinessSetting::setValue('pagination', 10, BusinessSetting::TYPE_INTEGER);
        BusinessSetting::setValue('company_copyright_text', 'nibizsoft.com', BusinessSetting::TYPE_STRING);
        BusinessSetting::setValue('currency_decimal_point_setting', 2, BusinessSetting::TYPE_INTEGER);
        
        // Shipping Settings
        BusinessSetting::setValue('shipping_responsibility', 'inhouse_shipping', BusinessSetting::TYPE_STRING);
        BusinessSetting::setValue('shipping_method_for_inhouse', 'order_wise', BusinessSetting::TYPE_STRING);
        BusinessSetting::setValue('shipping_fee', 20, BusinessSetting::TYPE_FLOAT);
        BusinessSetting::setValue('tax', 2, BusinessSetting::TYPE_FLOAT);
        
        // App Download Info
        BusinessSetting::setValue('apple_store_link', '', BusinessSetting::TYPE_STRING);
        BusinessSetting::setValue('apple_store_enabled', false, BusinessSetting::TYPE_BOOLEAN);
        BusinessSetting::setValue('google_play_store_link', 'https://play.google.com/store/apps/details?id=com.ecommatrix.customer&pcampaignid=web_share&pli=1', BusinessSetting::TYPE_STRING);
        BusinessSetting::setValue('google_play_store_enabled', true, BusinessSetting::TYPE_BOOLEAN);
        
        // Website Customization
        BusinessSetting::setValue('website_primary_color', '#000000', BusinessSetting::TYPE_STRING);
        BusinessSetting::setValue('website_secondary_color', '#EB0000', BusinessSetting::TYPE_STRING);
        BusinessSetting::setValue('website_header_logo', null, BusinessSetting::TYPE_STRING);
        BusinessSetting::setValue('website_footer_logo', null, BusinessSetting::TYPE_STRING);
        BusinessSetting::setValue('website_favicon', null, BusinessSetting::TYPE_STRING);
        BusinessSetting::setValue('website_loading_gif', null, BusinessSetting::TYPE_STRING);
        BusinessSetting::setValue('app_logo', null, BusinessSetting::TYPE_STRING);
        
        // Customer Settings
        BusinessSetting::setValue('customer_wallet_enabled', true, BusinessSetting::TYPE_BOOLEAN);
        BusinessSetting::setValue('customer_loyalty_point_enabled', true, BusinessSetting::TYPE_BOOLEAN);
        BusinessSetting::setValue('customer_referral_earning_enabled', true, BusinessSetting::TYPE_BOOLEAN);
        
        // Customer Wallet Settings
        BusinessSetting::setValue('add_refund_amount_to_wallet', false, BusinessSetting::TYPE_BOOLEAN);
        BusinessSetting::setValue('add_fund_to_wallet', true, BusinessSetting::TYPE_BOOLEAN);
        BusinessSetting::setValue('maximum_add_fund_amount', 10000, BusinessSetting::TYPE_FLOAT);
        BusinessSetting::setValue('minimum_add_fund_amount', 500, BusinessSetting::TYPE_FLOAT);
        
        // Customer Loyalty Point Settings
        BusinessSetting::setValue('equivalent_point_to_1_unit_currency', 1, BusinessSetting::TYPE_INTEGER);
        BusinessSetting::setValue('loyalty_point_earn_on_each_order_percent', 5, BusinessSetting::TYPE_FLOAT);
        BusinessSetting::setValue('minimum_point_required_to_convert', 1, BusinessSetting::TYPE_INTEGER);
        
        // Customer Referrer Settings
        BusinessSetting::setValue('earnings_to_each_referral', 100, BusinessSetting::TYPE_FLOAT);
        
        // Business Name (for API compatibility)
        BusinessSetting::setValue('business_name', 'Ecommatrix', BusinessSetting::TYPE_STRING);
        BusinessSetting::setValue('software_type', 'multi_vendor', BusinessSetting::TYPE_STRING);
    }
}
