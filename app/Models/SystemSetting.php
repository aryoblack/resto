<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $table = 'system_setting';

    public $timestamps = false;

    protected $fillable = [
        'key',
        'value',
    ];

    protected static function booted(): void
    {
        static::saved(fn (self $setting) => $setting->clearCache());
        static::deleted(fn (self $setting) => $setting->clearCache());
    }

    /**
     * Get a setting value by key.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        return \Illuminate\Support\Facades\Cache::remember("system_setting_{$key}", 86400, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set a setting value by key (upsert).
     */
    public static function setValue(string $key, mixed $value): static
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        return $setting;
    }

    private function clearCache(): void
    {
        \Illuminate\Support\Facades\Cache::forget("system_setting_{$this->key}");
        \Illuminate\Support\Facades\Cache::forget('system_settings_all');
    }
}
