<?php

namespace App\Services;

use App\Models\Branch;
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
     * Get all financial settings for POS & pricing. Branch-scoped — pass the
     * branch whose override should apply, or omit for the global default.
     */
    public static function getFinancialConfig($branchId = null)
    {
        return Cache::rememberForever(self::cacheKey('financial_config', $branchId), function () use ($branchId) {
            return [
                'vat_rate' => 0,
                'service_charge' => SystemSetting::get('service_charge', 0.00, $branchId),
                'currency' => 'PHP',
                'currency_symbol' => '₱',
                'discount_rate' => SystemSetting::get('discount_rate', 0.10, $branchId),
            ];
        });
    }

    /**
     * Get all inventory settings. Branch-scoped — pass the branch whose
     * override should apply, or omit for the global default.
     */
    public static function getInventoryConfig($branchId = null)
    {
        return Cache::rememberForever(self::cacheKey('inventory_config', $branchId), function () use ($branchId) {
            return [
                'low_stock_threshold' => SystemSetting::get('low_stock_threshold', 10, $branchId),
                'critical_stock_threshold' => SystemSetting::get('critical_stock_threshold', 5, $branchId),
                'expiry_alert_days' => SystemSetting::get('expiry_alert_days', 7, $branchId),
                'auto_reorder_enabled' => SystemSetting::get('auto_reorder_enabled', false, $branchId),
            ];
        });
    }

    /**
     * Get POS platform settings. Branch-scoped — pass the branch whose
     * override should apply, or omit for the global default.
     */
    public static function getPosConfig($branchId = null)
    {
        return Cache::rememberForever(self::cacheKey('pos_config', $branchId), function () use ($branchId) {
            return [
                'business_name' => SystemSetting::get('pos_business_name', 'Mister Takoyaki', $branchId),
                'order_types' => SystemSetting::get('pos_order_types', ['Dine-in', 'Take-out'], $branchId),
                'payment_methods' => SystemSetting::get('pos_payment_methods', ['Cash', 'GCash'], $branchId),
                'logo_enabled' => SystemSetting::get('receipt_logo_enabled', true, $branchId),
                'show_vat' => false,
                'footer_message' => SystemSetting::get('receipt_footer_message', 'Thank you for your visit!', $branchId),
                'return_policy' => SystemSetting::get('receipt_return_policy', 'No return, no exchange.', $branchId),
                'copies' => SystemSetting::get('receipt_copies', 1, $branchId),
                'qr_url' => SystemSetting::get('receipt_qr_url', '', $branchId),
                'kitchen_slip_title' => SystemSetting::get('kitchen_slip_title', '🍳 KITCHEN SLIP', $branchId),
                'kitchen_slip_subtitle' => SystemSetting::get('kitchen_slip_subtitle', 'Food Preparation Order', $branchId),
                'barista_slip_title' => SystemSetting::get('barista_slip_title', '☕ BARISTA SLIP', $branchId),
                'barista_slip_subtitle' => SystemSetting::get('barista_slip_subtitle', 'Beverage Preparation Order', $branchId),
                'customer_receipt_title' => SystemSetting::get('customer_receipt_title', 'Customer Receipt & Invoice', $branchId),
                'show_receipt_qr_code' => SystemSetting::get('show_receipt_qr_code', true, $branchId),
                'show_receipt_footer' => SystemSetting::get('show_receipt_footer', true, $branchId),
                'show_receipt_tendered' => SystemSetting::get('show_receipt_tendered', true, $branchId),
                'show_receipt_change' => SystemSetting::get('show_receipt_change', true, $branchId),
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
     * Builds a cache key that's distinct per branch, so a branch override
     * and the global default never collide in cache — the same way
     * SystemSetting itself distinguishes rows by (key, branch_id).
     */
    private static function cacheKey(string $base, $branchId = null): string
    {
        return $branchId ? "{$base}_branch_{$branchId}" : "{$base}_global";
    }

    /**
     * Invalidate all configuration caches (call after updates). Clears the
     * global config plus every branch's cached override — branch-scoped
     * config is cached per branch, so a single Cache::forget('pos_config')
     * would miss every branch-specific copy and leave stale data behind
     * after that branch's Settings are saved.
     */
    public static function invalidateCache()
    {
        Cache::forget('business_config');
        Cache::forget('accessibility_config');

        foreach (['financial_config', 'inventory_config', 'pos_config'] as $base) {
            Cache::forget(self::cacheKey($base, null));
            foreach (Branch::pluck('id') as $branchId) {
                Cache::forget(self::cacheKey($base, $branchId));
            }
        }
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