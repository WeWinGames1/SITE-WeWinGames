<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
    ];

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember("site_setting_{$key}", 3600, function () use ($key) {
            return static::where('key', $key)->first();
        });

        if (! $setting) {
            return $default;
        }

        return static::castValue($setting->value, $setting->type);
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, mixed $value): bool
    {
        $setting = static::where('key', $key)->first();

        if (! $setting) {
            return false;
        }

        $setting->update(['value' => static::prepareValue($value, $setting->type)]);

        Cache::forget("site_setting_{$key}");
        Cache::forget('site_settings_all');

        return true;
    }

    /**
     * Get all settings, optionally filtered by group.
     */
    public static function getAllSettings(?string $group = null): array
    {
        $cacheKey = $group ? "site_settings_{$group}" : 'site_settings_all';

        return Cache::remember($cacheKey, 3600, function () use ($group) {
            $query = static::query();

            if ($group) {
                $query->where('group', $group);
            }

            return $query->get()->mapWithKeys(function ($setting) {
                return [$setting->key => static::castValue($setting->value, $setting->type)];
            })->toArray();
        });
    }

    /**
     * Cast value to the appropriate type.
     */
    protected static function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Prepare value for storage.
     */
    protected static function prepareValue(mixed $value, string $type): string
    {
        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value),
            default => (string) $value,
        };
    }

    /**
     * Clear all settings cache.
     */
    public static function clearCache(): void
    {
        $settings = static::all();

        foreach ($settings as $setting) {
            Cache::forget("site_setting_{$setting->key}");
        }

        Cache::forget('site_settings_all');

        $groups = $settings->pluck('group')->unique();
        foreach ($groups as $group) {
            Cache::forget("site_settings_{$group}");
        }
    }
}
