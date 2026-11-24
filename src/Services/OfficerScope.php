<?php

namespace Bale\Sidulur\Services;

use App\Models\User;
use Bale\Cms\Services\TenantManager;
use Bale\Sidulur\Models\Officer;
use Bale\Sidulur\SidulurPermissions;

class OfficerScope
{
    /**
     * ID bidang yang boleh dilihat user. `null` berarti semua (admin/root).
     *
     * @return array<int, string>|null
     */
    public static function bidangIds(?User $user = null): ?array
    {
        $user ??= auth()->user();

        if (! $user) {
            return [];
        }

        if ($user->hasAnyRole(['root', 'bale-sidulur-admin'])) {
            return null;
        }

        $officer = Officer::query()
            ->where('user_id', $user->uuid)
            ->where('is_active', true)
            ->first();

        return $officer
            ? $officer->bidangs()->pluck('sidulur_bidangs.id')->all()
            : [];
    }

    public static function officer(?User $user = null): ?Officer
    {
        $user ??= auth()->user();

        if (! $user) {
            return null;
        }

        return Officer::query()
            ->where('user_id', $user->uuid)
            ->where('is_active', true)
            ->first();
    }

    public static function hasRole(string $flag, ?User $user = null): bool
    {
        $user ??= auth()->user();

        if ($user?->hasAnyRole(['root', 'bale-sidulur-admin'])) {
            return true;
        }

        return (bool) static::officer($user)?->{$flag};
    }

    /**
     * Permission yang boleh dipakai petugas: role landlord ∩ bendera petugas.
     */
    public static function grants(string $ability, ?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->hasAnyRole(['root', 'bale-sidulur-admin'])) {
            return true;
        }

        if (! TenantManager::getActiveConnection()) {
            return false;
        }

        $officer = static::officer($user);

        if (! $officer) {
            return false;
        }

        foreach (SidulurPermissions::FLAGS as $flag => $permissions) {
            if ($officer->{$flag} && in_array($ability, $permissions, true)) {
                return true;
            }
        }

        return false;
    }
}
