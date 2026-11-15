<?php

namespace Bale\Sidulur\Models;

use Bale\Cms\Traits\UsesTenantConnection;
use Bale\Sidulur\Support\BlindIndex;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FamilyCard extends Model
{
    use HasUuids;
    use SoftDeletes;
    use UsesTenantConnection;

    protected $table = 'sidulur_family_cards';

    protected $fillable = [
        'nomor_kk',
        'district_code',
        'district_name',
        'village_code',
        'village_name',
        'alamat',
    ];

    protected $hidden = ['nomor_kk', 'nomor_kk_hash'];

    protected $casts = [
        'nomor_kk' => 'encrypted',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            $normalized = BlindIndex::normalize($model->nomor_kk);

            if ($normalized === null) {
                throw new \InvalidArgumentException('Nomor KK wajib diisi.');
            }

            $model->nomor_kk = $normalized;
            $model->nomor_kk_hash = BlindIndex::make($normalized);
        });
    }

    public function citizens(): HasMany
    {
        return $this->hasMany(Citizen::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
