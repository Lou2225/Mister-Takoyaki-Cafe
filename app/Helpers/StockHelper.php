<?php

namespace App\Helpers;

use App\Models\Ingredient;

class StockHelper
{
    /**
     * Professional Base Unit Definitions
     * Standardized to the smallest consumption units to ensure 
     * precise recipe costing and inventory accuracy.
     */
    const UNITS = [
        'pcs'  => 'Pieces (pcs)',
        'g'    => 'Grams (g)',
        'ml'   => 'Milliliters (ml)',
    ];

    /**
     * Standard Bulk Ordering Units
     */
    const BULK_UNITS = [
        'box'    => 'Box',
        'sack'   => 'Sack',
        'bottle' => 'Bottle',
        'can'    => 'Can',
        'pack'   => 'Pack',
        'bundle' => 'Bundle',
        'tray'   => 'Tray',
        'pcs'    => 'Pieces (pcs)',
        'kg'     => 'Kilograms (kg)',
        'l'      => 'Liters (L)',
    ];

    /**
     * Convert standard units to base. Handles kg→g and l→ml only.
     * For custom packaging (box, bottle, sack, etc.) use convertToBase() instead.
     */
    public static function toBase($quantity, $unit)
    {
        $unit = strtolower($unit);
        $quantity = (float)$quantity;

        switch ($unit) {
            case 'kg':
            case 'kilograms':
                return $quantity * 1000;
            case 'l':
            case 'liters':
                return $quantity * 1000;
            case 'g':
            case 'grams':
            case 'ml':
            case 'milliliters':
            case 'pieces':
            case 'pcs':
            default:
                return $quantity;
        }
    }

    /**
     * Universal converter — looks up the ingredient's `ingredient_unit_conversions` table
     * first, then falls back to standard unit conversion (kg→g, l→ml).
     *
     * Always returns the quantity in base units (g / ml / pcs).
     *
     * Examples:
     *   convertToBase(2, 'box', $soySauce)   → 2 × 18,000 = 36,000 ml
     *   convertToBase(3, 'bottle', $soySauce) → 3 × 750   =  2,250 ml
     *   convertToBase(1, 'kg', $flour)        → 1,000 g   (standard fallback)
     */
    public static function convertToBase(float $quantity, string $unitName, Ingredient $ing): float
    {
        $unitName = strtolower(trim($unitName));

        // 1. Look up the custom conversion table for this ingredient
        $conversion = $ing->unitConversions()
            ->whereRaw('LOWER(unit_name) = ?', [$unitName])
            ->first();

        if ($conversion && $conversion->qty_in_base > 0) {
            return $quantity * $conversion->qty_in_base;
        }

        // 2. Fallback to standard unit conversion
        return self::toBase($quantity, $unitName);
    }

    /**
     * Get the cost per single base unit for a given packaging unit.
     * Used to auto-fill `unit_cost` on stock movements when stocking in bulk.
     *
     * e.g. box of soy sauce: ₱550 / 18,000 ml = ₱0.0306 per ml
     */
    public static function getPricePerBase(string $unitName, Ingredient $ing): float
    {
        $unitName = strtolower(trim($unitName));

        $conversion = $ing->unitConversions()
            ->whereRaw('LOWER(unit_name) = ?', [$unitName])
            ->first();

        if ($conversion && $conversion->qty_in_base > 0 && $conversion->price_per_unit > 0) {
            return $conversion->price_per_unit / $conversion->qty_in_base;
        }

        return (float) ($ing->cost ?? 0);
    }

    /**
     * Get the professional abbreviation from a unit key.
     */
    public static function getAbbreviation($unit)
    {
        $unit = strtolower($unit);
        if ($unit === 'grams') return 'g';
        if ($unit === 'milliliters') return 'ml';
        if ($unit === 'pieces') return 'pcs';

        $unitLabel = self::UNITS[$unit] ?? $unit;
        preg_match('/\((.*?)\)/', $unitLabel, $matches);
        return $matches[1] ?? $unit;
    }

    /**
     * Format a quantity for display (e.g., 1500g -> 1.5 kg).
     */
    public static function formatForDisplay($quantity, $baseUnit)
    {
        $baseUnit = strtolower($baseUnit);
        $quantity = (float)$quantity;

        if ($baseUnit === 'grams' || $baseUnit === 'g' || $baseUnit === 'kg') {
            if ($quantity >= 1000) {
                return rtrim(rtrim(number_format($quantity / 1000, 2), '0'), '.') . ' kg';
            }
            return rtrim(rtrim(number_format($quantity, 2), '0'), '.') . ' g';
        }

        if ($baseUnit === 'ml' || $baseUnit === 'milliliters' || $baseUnit === 'l') {
            if ($quantity >= 1000) {
                return rtrim(rtrim(number_format($quantity / 1000, 2), '0'), '.') . ' L';
            }
            return rtrim(rtrim(number_format($quantity, 2), '0'), '.') . ' ml';
        }

        $abbr = self::getAbbreviation($baseUnit);
        return rtrim(rtrim(number_format($quantity, 2), '0'), '.') . ' ' . $abbr;
    }

    /**
     * Get the default input unit for a given ingredient base unit.
     */
    public static function getDefaultInputUnit(string $baseUnit): string
    {
        $baseUnit = strtolower($baseUnit);
        $map = [
            'grams'       => 'g',
            'g'           => 'g',
            'kg'          => 'g',
            'milliliters' => 'ml',
            'ml'          => 'ml',
            'l'           => 'ml',
            'pieces'      => 'pcs',
            'pcs'         => 'pcs',
        ];
        return $map[$baseUnit] ?? 'pcs';
    }
}
