<?php

namespace Bale\Sidulur\Models;

use Bale\Cms\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pendaftaran extends Model
{
    use HasUuids;
    use SoftDeletes;
    use UsesTenantConnection;

    public const STATUS_BARU = 'baru';

    public const STATUS_SELESAI = 'selesai';

    protected $table = 'sidulur_pendaftarans';

    protected $fillable = [
        'ticket_number',
        'citizen_id',
        'service_id',
        'keperluan',
        'form_snapshot',
        'form_data',
        'status',
        'forwarded_at',
        'created_by',
        'completed_at',
        'expires_at',
    ];

    protected $casts = [
        'form_snapshot' => 'array',
        'form_data' => 'encrypted:array',
        'forwarded_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function pelayananBidang(): HasOne
    {
        return $this->hasOne(PelayananBidang::class);
    }

    public function timelines(): HasMany
    {
        return $this->hasMany(Timeline::class)->orderBy('created_at');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
