<?php

namespace Bale\Sidulur\Models;

use Bale\Cms\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bidang extends Model
{
    use HasUuids;
    use SoftDeletes;
    use UsesTenantConnection;

    protected $table = 'sidulur_bidangs';

    protected $fillable = ['nama', 'deskripsi', 'icon', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function officers(): BelongsToMany
    {
        return $this->belongsToMany(Officer::class, 'sidulur_bidang_officer');
    }

    public function pelayananBidangs(): HasMany
    {
        return $this->hasMany(PelayananBidang::class);
    }
}
