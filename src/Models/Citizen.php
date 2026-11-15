<?php

namespace Bale\Sidulur\Models;

use Bale\Cms\Traits\UsesTenantConnection;
use Bale\Sidulur\Support\BlindIndex;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Citizen extends Model
{
    use HasUuids;
    use SoftDeletes;
    use UsesTenantConnection;

    protected $table = 'sidulur_citizens';

    protected $fillable = [
        'nama',
        'nik',
        'phone',
        'family_card_id',
        'is_active',
    ];

    protected $hidden = ['nik', 'nik_hash', 'phone', 'phone_hash', 'nrs'];

    protected $casts = [
        'nik' => 'encrypted',
        'phone' => 'encrypted',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            $nik = BlindIndex::normalize($model->nik);

            if ($nik === null) {
                throw new \InvalidArgumentException('NIK wajib diisi.');
            }

            $model->nik = $nik;
            $model->nik_hash = BlindIndex::make($nik);

            $phone = BlindIndex::normalize($model->phone);
            $model->phone = $phone;
            $model->phone_hash = BlindIndex::make($phone);

            if (! $model->nrs) {
                $model->nrs = static::nextNrs();
            }
        });
    }

    public static function nextNrs(): string
    {
        do {
            $nrs = str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        } while (static::withTrashed()->where('nrs', $nrs)->exists());

        return $nrs;
    }

    public function familyCard(): BelongsTo
    {
        return $this->belongsTo(FamilyCard::class);
    }

    public function pendaftarans(): HasMany
    {
        return $this->hasMany(Pendaftaran::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function scopeWithIdentity(Builder $query, string $value): Builder
    {
        $hash = BlindIndex::make($value);

        return $query->where(function (Builder $query) use ($hash) {
            $query->where('nik_hash', $hash)
                ->orWhere('phone_hash', $hash)
                ->orWhereHas('familyCard', fn (Builder $q) => $q->where('nomor_kk_hash', $hash));
        });
    }

    public static function searchForTable(Builder $builder, string $query, array $searchable): Builder
    {
        $term = trim($query);
        $hash = BlindIndex::make($term);

        return $builder->where(function (Builder $builder) use ($term, $hash, $searchable) {
            foreach ($searchable as $column) {
                $builder->orWhere($column, 'like', '%'.$term.'%');
            }

            if ($hash) {
                $builder->orWhere('nik_hash', $hash)
                    ->orWhere('phone_hash', $hash)
                    ->orWhereHas('familyCard', fn (Builder $q) => $q->where('nomor_kk_hash', $hash));
            }
        });
    }

    public function maskedNik(): string
    {
        $nik = (string) $this->nik;

        return strlen($nik) >= 8
            ? substr($nik, 0, 4).str_repeat('*', strlen($nik) - 8).substr($nik, -4)
            : '—';
    }

    public function hasDocument(string $kind): bool
    {
        return $this->documents()->where('kind', $kind)->exists();
    }
}
