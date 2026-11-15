<?php

namespace Bale\Sidulur\Models;

use App\Models\User;
use Bale\Cms\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Officer extends Model
{
    use HasUuids;
    use UsesTenantConnection;

    protected $table = 'sidulur_officers';

    protected $fillable = [
        'user_id',
        'is_pendaftaran',
        'is_bidang',
        'is_lapangan',
        'is_active',
    ];

    protected $casts = [
        'is_pendaftaran' => 'boolean',
        'is_bidang' => 'boolean',
        'is_lapangan' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'uuid');
    }

    public function bidangs(): BelongsToMany
    {
        return $this->belongsToMany(Bidang::class, 'sidulur_bidang_officer');
    }

    public function pelayananBidangs(): HasMany
    {
        return $this->hasMany(PelayananBidang::class);
    }

    /**
     * Pencarian tabel: nama atau email user. User ada di DB landlord, jadi
     * subquery lintas database tidak dipakai — id user dicari lebih dulu.
     */
    public static function searchForTable(Builder $builder, string $query, array $searchable = []): Builder
    {
        $term = trim($query);

        $userIds = User::query()
            ->where(fn (Builder $user) => $user
                ->where('name', 'like', '%'.$term.'%')
                ->orWhere('email', 'like', '%'.$term.'%'))
            ->pluck('uuid');

        return $builder->whereIn('user_id', $userIds);
    }
}
