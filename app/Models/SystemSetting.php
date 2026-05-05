<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value'];

    /**
     * Get a setting by key.
     */
    public static function get($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        if (!$setting) return $default;

        $value = $setting->value;
        
        // Auto-decode JSON if it looks like it
        if (str_starts_with($value, '[') || str_starts_with($value, '{')) {
            $decoded = json_decode($value, true);
            return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
        }

        // Auto-cast booleans and numbers
        if ($value === 'true') return true;
        if ($value === 'false') return false;
        if (is_numeric($value)) return $value + 0;

        return $value;
    }

    /**
     * Set a setting by key.
     */
    public static function set($key, $value)
    {
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        } elseif (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }

        return self::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
