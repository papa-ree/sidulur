<?php

namespace Bale\Sidulur\Support;

use Bale\Sidulur\Models\PelayananBidang;
use Bale\Sidulur\Services\OfficerScope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Syarat query untuk tabel antrean pelayanan di
 * `<livewire:core-shared-components::data-table>` (`queryUsing`), karena
 * `constraints` hanya bisa `where(kolom, nilai)`.
 */
class PelayananTable
{
    public static function verlap(Builder $builder): void
    {
        static::scoped($builder)->where('status', PelayananBidang::STATUS_DIJADWALKAN);
    }

    public static function pelayanan(Builder $builder): void
    {
        static::scoped($builder)->where('status', '!=', PelayananBidang::STATUS_SELESAI);
    }

    public static function selesai(Builder $builder): void
    {
        static::scoped($builder)->where('status', PelayananBidang::STATUS_SELESAI);
    }

    protected static function scoped(Builder $builder): Builder
    {
        $bidangIds = OfficerScope::bidangIds();

        return $bidangIds === null ? $builder : $builder->whereIn('bidang_id', $bidangIds);
    }
}
