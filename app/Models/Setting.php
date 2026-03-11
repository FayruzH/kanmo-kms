<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value_json',
    ];

    protected $casts = [
        'value_json' => 'array',
    ];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->find($key);
        if (!$setting) {
            return $default;
        }

        return $setting->value_json ?? $default;
    }
}
