<?php

namespace Bale\Sidulur\Models;

use Bale\Cms\Traits\UsesTenantConnection;
use Bale\Core\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Support\LogOptions;

class Timeline extends Model
{
    use HasUuids;
    use LogsActivity;
    use UsesTenantConnection;

    protected $table = 'sidulur_timelines';

    protected $fillable = [
        'pendaftaran_id',
        'pelayanan_bidang_id',
        'stage',
        'from_status',
        'to_status',
        'note',
        'actor_id',
    ];

    /**
     * Catatan bebas dapat memuat PII; yang dicatat hanya perpindahan status.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['pendaftaran_id', 'pelayanan_bidang_id', 'stage', 'from_status', 'to_status', 'actor_id'])
            ->logOnlyDirty()
            ->useLogName('sidulur');
    }

    public function pendaftaran(): BelongsTo
    {
        return $this->belongsTo(Pendaftaran::class);
    }

    public function pelayananBidang(): BelongsTo
    {
        return $this->belongsTo(PelayananBidang::class);
    }
}
