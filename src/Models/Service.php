<?php

namespace Bale\Sidulur\Models;

use Bale\Cms\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasUuids;
    use SoftDeletes;
    use UsesTenantConnection;

    protected $table = 'sidulur_services';

    protected $fillable = [
        'bidang_id',
        'nama',
        'slug',
        'icon',
        'deskripsi',
        'selesai_di_pendaftaran',
        'butuh_verifikasi',
        'sla_value',
        'sla_unit',
        'is_active',
        'sort',
    ];

    protected $casts = [
        'selesai_di_pendaftaran' => 'boolean',
        'butuh_verifikasi' => 'boolean',
        'is_active' => 'boolean',
        'sla_value' => 'integer',
        'sort' => 'integer',
    ];

    public function bidang(): BelongsTo
    {
        return $this->belongsTo(Bidang::class);
    }

    public function form(): HasOne
    {
        return $this->hasOne(Form::class);
    }

    public function pendaftarans(): HasMany
    {
        return $this->hasMany(Pendaftaran::class);
    }
}
