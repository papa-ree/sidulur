<?php

namespace Bale\Sidulur\Models;

use Bale\Cms\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Document extends Model
{
    use HasUuids;
    use UsesTenantConnection;

    public const PUBLIC_KINDS = ['hasil', 'surat'];

    protected $table = 'sidulur_documents';

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
