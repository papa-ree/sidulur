<?php

namespace Bale\Sidulur\Models;

use Bale\Cms\Traits\UsesTenantConnection;
use Bale\Sidulur\Support\FormSchema;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Form extends Model
{
    use HasUuids;
    use UsesTenantConnection;

    protected $table = 'sidulur_forms';

    protected $fillable = ['service_id', 'nama', 'schema', 'version'];

    protected $casts = [
        'schema' => 'array',
        'version' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            $model->schema = FormSchema::normalize($model->schema);
        });
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
