<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['user_id', 'key', 'value'];

    protected static function booted()
    {
        static::saved(function ($setting) {
            \Illuminate\Support\Facades\Cache::forget("user_{$setting->user_id}_settings");
        });

        static::deleted(function ($setting) {
            \Illuminate\Support\Facades\Cache::forget("user_{$setting->user_id}_settings");
        });
    }

    public static function getForUser($userId, $key, $default = null)
    {
        $settings = \Illuminate\Support\Facades\Cache::rememberForever("user_{$userId}_settings", function () use ($userId) {
            return static::where('user_id', $userId)->pluck('value', 'key')->toArray();
        });

        return $settings[$key] ?? $default;
    }
}
