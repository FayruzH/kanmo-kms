<?php

namespace App\Services;

use App\Models\Setting;

class SettingService
{
    public function getInt(string $key, int $default): int
    {
        $value = Setting::getValue($key, $default);
        if (is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }

    public function getArray(string $key, array $default): array
    {
        $value = Setting::getValue($key, $default);
        return is_array($value) ? $value : $default;
    }

    public function getString(string $key, string $default): string
    {
        $value = Setting::getValue($key, $default);
        return is_string($value) ? $value : $default;
    }
}
