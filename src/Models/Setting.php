<?php

namespace Bale\Sidulur\Models;

use Bale\Cms\Services\TenantManager;
use Bale\Cms\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class Setting extends Model
{
    use UsesTenantConnection;

    protected $table = 'sidulur_settings';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = Cache::rememberForever(static::cacheKey($key), function () use ($key) {
            return static::query()->find($key)?->value;
        });

        return $value ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(static::cacheKey($key));
    }

    public static function checkPassword(?string $password): bool
    {
        $hash = static::get('dashboard_password');

        return is_string($hash) && $hash !== '' && Hash::check((string) $password, $hash);
    }

    public static function setPassword(string $password): void
    {
        static::put('dashboard_password', Hash::make($password));
    }

    protected static function cacheKey(string $key): string
    {
        // Cache store admin ada di landlord: tanpa segmen tenant, password
        // dashboard satu tenant terbaca oleh tenant lain.
        return 'sidulur.setting.'.(TenantManager::getActiveConnection() ?? 'default').'.'.$key;
    }
}
