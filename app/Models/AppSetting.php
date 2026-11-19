<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public static function flag(string $key, bool $default = true): bool
    {
        $value = static::query()->where('key', $key)->value('value');

        return $value === null ? $default : (bool) json_decode((string) $value);
    }

    public static function put(string $key, bool $value): void
    {
        static::set($key, $value);
    }

    public static function value(string $key, mixed $default = null): mixed
    {
        $value = static::query()->where('key', $key)->value('value');

        return $value === null ? $default : json_decode((string) $value, true);
    }

    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => json_encode($value)]);
    }
}
