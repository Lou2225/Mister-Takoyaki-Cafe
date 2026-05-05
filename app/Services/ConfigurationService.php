<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

class ConfigurationService
{
    /**
     * Get all business settings for sync across modules
     */
    public static function getBusinessConfig()
    {
        return Cache::rememberForever('business_config', function () {
            return [
                'name' => SystemSetting::get('business_name', 'Mister Takoyaki Cafe'),
                'email' => SystemSetting::get('business_email', 'contact@mistertakoyaki.com'),
                'phone' => SystemSetting::get('business_phone', ''),
                'tin' => SystemSetting::get('business_tin', ''),
                'address' => (function() {
                    $addr = SystemSetting::get('business_address', '');
                    return is_array($addr) ? ($addr['formatted'] ?? '') : $addr;
                })(),
                'logo' => SystemSetting::get('business_logo', ''),
            ];
        });
    }

    /**
     * Get all financial settings for POS & pricing
     */
    public static function getFinancialConfig()
    {
        return Cache::rememberForever('financial_config', function () {
            return [
                'vat_rate' => 0,
                'service_charge' => SystemSetting::get('service_charge', 0.00),
                'currency' => 'PHP',
                'currency_symbol' => '₱',
                'discount_rate' => SystemSetting::get('discount_rate', 0.10),
            ];
        });
    }

    /**
     * Get all inventory settings
     */
    public static function getInventoryConfig()
    {
        return Cache::rememberForever('inventory_config', function () {
            return [
                'low_stock_threshold' => SystemSetting::get('low_stock_threshold', 10),
                'critical_stock_threshold' => SystemSetting::get('critical_stock_threshold', 5),
                'expiry_alert_days' => SystemSetting::get('expiry_alert_days', 7),
                'auto_reorder_enabled' => SystemSetting::get('auto_reorder_enabled', false),
            ];
        });
    }

    /**
     * Get POS platform settings
     */
    public static function getPosConfig()
    {
        return Cache::rememberForever('pos_config', function () {
            return [
                'business_name' => SystemSetting::get('pos_business_name', 'Mister Takoyaki'),
                'order_types' => SystemSetting::get('pos_order_types', ['Dine-in', 'Take-out']),
                'payment_methods' => SystemSetting::get('pos_payment_methods', ['Cash', 'GCash']),
                'logo_enabled' => SystemSetting::get('receipt_logo_enabled', true),
                'show_vat' => false,
                'footer_message' => SystemSetting::get('receipt_footer_message', 'Thank you for your visit!'),
                'return_policy' => SystemSetting::get('receipt_return_policy', 'No return, no exchange.'),
                'copies' => SystemSetting::get('receipt_copies', 1),
                'qr_url' => SystemSetting::get('receipt_qr_url', ''),
            ];
        });
    }

    /**
     * Get system accessibility settings
     */
    public static function getAccessibilityConfig()
    {
        return Cache::rememberForever('accessibility_config', function () {
            return [
                'hide_operational_modules' => SystemSetting::get('hide_operational_modules', false),
            ];
        });
    }

    /**
     * Invalidate all configuration caches (call after updates)
     */
    public static function invalidateCache()
    {
        Cache::forget('business_config');
        Cache::forget('financial_config');
        Cache::forget('inventory_config');
        Cache::forget('pos_config');
        Cache::forget('accessibility_config');
    }

    /**
     * Get currency symbol for formatting
     */
    public static function getCurrencySymbol()
    {
        return '₱';
    }

    /**
     * Get VAT rate as percentage
     */
    public static function getVatRate()
    {
        $config = self::getFinancialConfig();
        return 0;
    }

    /**
     * Get business logo URL
     */
    public static function getBusinessLogoUrl()
    {
        $config = self::getBusinessConfig();
        return $config['logo'] ? asset('storage/' . $config['logo']) : null;
    }

    /**
     * Get business name
     */
    public static function getBusinessName()
    {
        $config = self::getBusinessConfig();
        return $config['name'] ?? 'Mister Takoyaki Cafe';
    }
}
