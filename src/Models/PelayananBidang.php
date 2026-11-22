<?php

namespace Bale\Sidulur\Models;

use Bale\Cms\Traits\UsesTenantConnection;
use Bale\Core\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;

class PelayananBidang extends Model
{
    use HasUuids;
    use LogsActivity;
    use SoftDeletes;
    use UsesTenantConnection;

    public const STATUS_BARU = 'baru';

    public const STATUS_DIJADWALKAN = 'dijadwalkan';

    public const STATUS_VERLAP_SELESAI = 'verlap_selesai';

    public const STATUS_SELESAI = 'selesai';

    protected $table = 'sidulur_pelayanan_bidangs';

    protected $fillable = [
        'pendaftaran_id',
        'bidang_id',
        'service_id',
        'officer_id',
        'status',
        'scheduled_at',
        'scheduled_note',
        'form_snapshot',
        'form_data',
        'verlap_snapshot',
        'verlap_data',
        'verlap_at',
        'handled_by',
        'completed_at',
    ];

    protected $casts = [
        'scheduled_at' => 'date',
        'form_snapshot' => 'array',
        'form_data' => 'encrypted:array',
        'verlap_snapshot' => 'array',
        'verlap_data' => 'encrypted:array',
        'verlap_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Jawaban formulir dan catatan lapangan dapat memuat PII, jadi tidak ikut
     * dicatat ke activity_log yang berada di landlord.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logExcept(['form_data', 'verlap_data', 'form_snapshot', 'verlap_snapshot', 'scheduled_note'])
            ->logOnlyDirty()
            ->useLogName('sidulur');
    }

    public function pendaftaran(): BelongsTo
    {
        return $this->belongsTo(Pendaftaran::class);
    }

    public function bidang(): BelongsTo
    {
        return $this->belongsTo(Bidang::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class);
    }

    /**
     * Respon bidang baru boleh diisi setelah verlap selesai (bila layanan
     * membutuhkannya) atau sejak status baru untuk layanan tanpa verlap.
     */
    public function bolehRespon(): bool
    {
        if ($this->service?->butuh_verifikasi) {
            return $this->status === self::STATUS_VERLAP_SELESAI;
        }

        return in_array($this->status, [self::STATUS_BARU, self::STATUS_VERLAP_SELESAI], true);
    }

    /**
     * Dokumen hasil diunggah setelah verlap selesai; tetap boleh diganti
     * setelah pengajuan selesai.
     */
    public function bolehUnggahHasil(): bool
    {
        $allowed = $this->service?->butuh_verifikasi
            ? [self::STATUS_VERLAP_SELESAI, self::STATUS_SELESAI]
            : [self::STATUS_BARU, self::STATUS_SELESAI];

        return in_array($this->status, $allowed, true);
    }

    public function timelines(): HasMany
    {
        return $this->hasMany(Timeline::class);
    }

    /**
     * Pencarian tabel: nomor tiket atau nama warga (keduanya di tabel lain).
     */
    public static function searchForTable(Builder $builder, string $query, array $searchable = []): Builder
    {
        $term = trim($query);

        return $builder->whereHas('pendaftaran', function (Builder $pendaftaran) use ($term) {
            $pendaftaran->where('ticket_number', 'like', '%'.$term.'%')
                ->orWhereHas('citizen', fn (Builder $citizen) => $citizen->where('nama', 'like', '%'.$term.'%'));
        });
    }
}
