<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Accesso in sola lettura alle impostazioni salvate in storage/app/settings.json.
 * Condivide la stessa cache ('app_settings') usata da SettingsController.
 */
class Settings
{
    public static function all(): array
    {
        return Cache::remember('app_settings', 3600, function () {
            $path = storage_path('app/settings.json');

            if (file_exists($path)) {
                return json_decode(file_get_contents($path), true) ?: [];
            }

            return [];
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::all()[$key] ?? $default;
    }

    /**
     * Modalità "coming soon" / manutenzione attiva.
     */
    public static function comingSoon(): bool
    {
        return (bool) self::get('maintenance_mode', false);
    }
}
