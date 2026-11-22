<?php

namespace Bale\Sidulur\Models;

use Bale\Cms\Traits\UsesTenantConnection;
use Bale\Core\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\Support\LogOptions;

class Document extends Model
{
    use HasUuids;
    use LogsActivity;
    use UsesTenantConnection;

    public const PUBLIC_KINDS = ['hasil', 'surat'];

    protected $table = 'sidulur_documents';

    /**
     * Nama berkas asli dan path tidak dicatat karena dapat memuat identitas.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['documentable_type', 'documentable_id', 'kind', 'disk', 'mime', 'size', 'uploaded_by'])
            ->logOnlyDirty()
            ->useLogName('sidulur');
    }

    protected $fillable = [
        'documentable_type',
        'documentable_id',
        'kind',
        'disk',
        'path',
        'mime',
        'size',
        'original_name',
        'uploaded_by',
    ];

    protected $casts = ['size' => 'integer'];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isPublic(): bool
    {
        return in_array($this->kind, self::PUBLIC_KINDS, true);
    }
}
