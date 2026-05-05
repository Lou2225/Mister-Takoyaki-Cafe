<?php

namespace App\Helpers;

use App\Services\ConfigurationService;

class PricingHelper
{
    /**
     * Calculate final price with VAT
     */
    public static function withVat($basePrice)
    {
        return $basePrice;
    }

    /**
     * Calculate VAT amount
     */
    public static function calculateVat($basePrice)
    {
        return 0;
    }

    /**
     * Apply discount to price
     */
    public static function applyDiscount($price, $discountRate = null)
    {
        if ($discountRate === null) {
            $config = ConfigurationService::getFinancialConfig();
            $discountRate = $config['discount_rate'] ?? 0;
        }
        return $price * (1 - $discountRate);
    }

    /**
     * Format price with currency symbol
     */
    public static function format($price)
    {
        $symbol = ConfigurationService::getCurrencySymbol();
        return $symbol . number_format($price, 2);
    }

    /**
     * Get currency symbol
     */
    public static function getCurrency()
    {
        return ConfigurationService::getCurrencySymbol();
    }

    /**
     * Check if price is below low stock threshold
     */
    public static function isLowStock($quantity, $minimumStock = null)
    {
        if ($minimumStock === null) {
            $config = ConfigurationService::getInventoryConfig();
            $minimumStock = $config['low_stock_threshold'] ?? 10;
        }
        return $quantity <= $minimumStock;
    }

    /**
     * Check if price is at critical level
     */
    public static function isCriticalStock($quantity, $criticalStock = null)
    {
        if ($criticalStock === null) {
            $config = ConfigurationService::getInventoryConfig();
            $criticalStock = $config['critical_stock_threshold'] ?? 5;
        }
        return $quantity <= $criticalStock;
    }
}
