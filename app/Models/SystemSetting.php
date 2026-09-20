<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'branch_id'];

    /**
     * In-memory cache for all settings within the current lifecycle/request.
     */
    protected static ?array $runtimeCache = null;

    protected static function booted()
    {
        static::saved(fn () => self::clearRuntimeCache());
        static::deleted(fn () => self::clearRuntimeCache());
    }

    public static function clearRuntimeCache(): void
    {
        self::$runtimeCache = null;
    }

    protected static function ensureLoaded(): void
    {
        if (self::$runtimeCache !== null) {
            return;
        }

        self::$runtimeCache = [];
        try {
            $records = self::all();
            foreach ($records as $record) {
                $branchKey = $record->branch_id !== null ? (string)$record->branch_id : '__global__';
                self::$runtimeCache[$branchKey][$record->key] = $record->value;
            }
        } catch (\Throwable $e) {
            // Fallback gracefully if database table is not yet migrated/available
            self::$runtimeCache = [];
        }
    }

    /**
     * Get a setting by key. Pass $branchId to look up a branch-specific
     * override first, falling back to the global (branch_id null) row.
     * Omitting $branchId behaves exactly as before — a plain global lookup.
     */
    public static function get($key, $default = null, $branchId = null)
    {
        self::ensureLoaded();

        $branchKey = $branchId !== null ? (string)$branchId : null;
        $value = null;

        if ($branchKey !== null && isset(self::$runtimeCache[$branchKey][$key])) {
            $value = self::$runtimeCache[$branchKey][$key];
        } elseif (isset(self::$runtimeCache['__global__'][$key])) {
            $value = self::$runtimeCache['__global__'][$key];
        } else {
            return $default;
        }

        // Auto-decode JSON if it looks like it
        if (is_string($value) && (str_starts_with($value, '[') || str_starts_with($value, '{'))) {
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
     * Set a setting by key. Pass $branchId to write a branch-specific
     * override; omit it (or pass null) to write the global default.
     */
    public static function set($key, $value, $branchId = null)
    {
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        } elseif (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }

        self::clearRuntimeCache();
        return self::updateOrCreate(['key' => $key, 'branch_id' => $branchId], ['value' => $value]);
    }
}