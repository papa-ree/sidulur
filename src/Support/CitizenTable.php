<?php

namespace Bale\Sidulur\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Syarat query tabel Data Terhapus. Data diambil dari arsip soft delete,
 * sehingga pencarian dan paginasi tetap memakai data-table yang sama.
 */
class CitizenTable
{
    public static function trashed(Builder $builder): void
    {
        $builder->onlyTrashed();
    }
}
