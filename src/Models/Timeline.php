<?php

namespace Bale\Sidulur\Models;

use Bale\Cms\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Timeline extends Model
{
    use HasUuids;
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

    public function pendaftaran(): BelongsTo
    {
        return $this->belongsTo(Pendaftaran::class);
    }

    public function pelayananBidang(): BelongsTo
    {
        return $this->belongsTo(PelayananBidang::class);
    }
}
