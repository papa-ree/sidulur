<?php

namespace Bale\Sidulur\Models;

use Bale\Cms\Traits\UsesTenantConnection;
use Bale\Core\Traits\LogsActivity;
use Bale\Sidulur\Support\FormSchema;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Support\LogOptions;

class Form extends Model
{
    use HasUuids;
    use LogsActivity;
    use UsesTenantConnection;

    protected $table = 'sidulur_forms';

    protected $fillable = ['service_id', 'nama', 'schema', 'version'];

    protected $casts = [
        'schema' => 'array',
        'version' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty()->useLogName('sidulur');
    }

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
