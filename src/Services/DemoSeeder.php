<?php

namespace Bale\Sidulur\Services;

use Bale\Sidulur\Models\Citizen;
use Bale\Sidulur\Models\FamilyCard;
use Bale\Sidulur\Models\Pendaftaran;
use Bale\Sidulur\Models\Service;
use Bale\Sidulur\Models\Setting;
use Bale\Sidulur\Support\BlindIndex;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Data contoh untuk demo dan uji tampilan: kartu keluarga, warga beserta
 * dokumen KTP/KK, lalu pengajuan pada seluruh kombinasi status.
 *
 * Idempotent: kartu/warga dicocokkan lewat hash NIK/KK, dan satu warga demo
 * hanya dibuatkan satu pengajuan.
 */
class DemoSeeder
{
    public const DASHBOARD_PASSWORD = 'sidulur-demo';

    public function __construct(protected SubmissionFlow $flow) {}

    /**
     * @return array{kartu_keluarga: int, warga: int, dokumen: int, pendaftaran: int, password: ?string}
     */
    public function run(): array
    {
        $services = $this->services();
        $citizens = $this->citizens();
        $documents = $this->documents($citizens);

        return [
            'kartu_keluarga' => FamilyCard::query()->count(),
            'warga' => Citizen::query()->count(),
            'dokumen' => $documents,
            'pendaftaran' => $this->pendaftarans($services, $citizens),
            'password' => $this->dashboardPassword(),
        ];
    }

    /**
     * @return array<string, Service>
     */
    protected function services(): array
    {
        $slugs = array_column($this->cases(), 'service');

        $services = Service::query()->whereIn('slug', array_unique($slugs))->get()->keyBy('slug');
        $missing = array_diff(array_unique($slugs), $services->keys()->all());

        if ($missing !== []) {
            throw new \RuntimeException(
                'Layanan berikut belum ada: '.implode(', ', $missing).'. Jalankan `php artisan sidulur:seed` lebih dulu.'
            );
        }

        return $services->all();
    }

    /**
     * @return array<int, Citizen> urutan tetap, dipakai indeks pada cases()
     */
    protected function citizens(): array
    {
        $citizens = [];

        foreach ($this->familyCards() as $row) {
            $card = FamilyCard::query()->where('nomor_kk_hash', BlindIndex::make($row['nomor_kk']))->first()
                ?? FamilyCard::query()->create([
                    'nomor_kk' => $row['nomor_kk'],
                    'district_code' => $row['district_code'],
                    'district_name' => $row['district_name'],
                    'village_code' => $row['village_code'],
                    'village_name' => $row['village_name'],
                    'alamat' => $row['alamat'],
                ]);

            foreach ($row['anggota'] as $member) {
                $citizens[] = Citizen::query()->where('nik_hash', BlindIndex::make($member['nik']))->first()
                    ?? Citizen::query()->create([
                        'nama' => $member['nama'],
                        'nik' => $member['nik'],
                        'phone' => $member['phone'] ?? null,
                        'family_card_id' => $card->getKey(),
                    ]);
            }
        }

        return $citizens;
    }

    /**
     * KTP per warga, KK per kartu keluarga. File placeholder ditulis ke disk
     * agar unduhan dokumen pada UI tidak kosong.
     *
     * @param  array<int, Citizen>  $citizens
     */
    protected function documents(array $citizens): int
    {
        $count = 0;

        foreach ($citizens as $citizen) {
            $count += $this->attachDocument($citizen, 'ktp');

            if ($citizen->familyCard) {
                $count += $this->attachDocument($citizen->familyCard, 'kk');
            }
        }

        return $count;
    }

    protected function attachDocument(Model $owner, string $kind): int
    {
        if ($owner->documents()->where('kind', $kind)->exists()) {
            return 0;
        }

        $disk = config('sidulur.storage.disk', 'sidulur');
        $path = 'sidulur/demo/'.$kind.'/'.$owner->getKey().'.pdf';
        $content = "%PDF-1.4\n% Dokumen contoh Sidulur (".$kind.")\n%%EOF\n";

        Storage::disk($disk)->put($path, $content);

        $owner->documents()->create([
            'kind' => $kind,
            'disk' => $disk,
            'path' => $path,
            'mime' => 'application/pdf',
            'size' => strlen($content),
            'original_name' => strtoupper($kind).'-'.substr($owner->getKey(), 0, 8).'.pdf',
        ]);

        return 1;
    }

    /**
     * @param  array<string, Service>  $services
     * @param  array<int, Citizen>  $citizens
     */
    protected function pendaftarans(array $services, array $citizens): int
    {
        $count = 0;

        foreach ($this->cases() as $case) {
            $citizen = $citizens[$case['person']];

            if ($citizen->pendaftarans()->exists()) {
                continue;
            }

            $service = $services[$case['service']];
            $pendaftaran = $this->flow->register($citizen, $service, $case['keperluan'], $this->formData($service));

            $this->applyStage($case['stage'], $pendaftaran, $service);

            $base = now()->subDays($case['days_ago'])->setTime(8, 30);
            $pendaftaran->forceFill(['ticket_number' => 'SDLR-'.$base->format('Ymd').'-'.$case['suffix']])->saveQuietly();
            $this->backdate($pendaftaran, $base);

            $count++;
        }

        return $count;
    }

    protected function applyStage(string $stage, Pendaftaran $pendaftaran, Service $service): void
    {
        if ($stage === 'desk') {
            $this->flow->finishAtDesk($pendaftaran, 'Diselesaikan di meja pendaftaran.');

            return;
        }

        if ($stage === 'baru') {
            return;
        }

        $pelayanan = $this->flow->forward($pendaftaran, 'Diteruskan ke '.$service->bidang?->nama.'.');

        if ($stage === 'antrean') {
            return;
        }

        if ($stage === 'dijadwalkan') {
            $this->flow->schedule($pelayanan, now()->addDays(2)->toDateString(), 'Verifikasi lapangan dijadwalkan.');

            return;
        }

        $this->flow->schedule($pelayanan, now()->subDays(2)->toDateString(), 'Verifikasi lapangan dijadwalkan.');
        $this->flow->recordVerlap($pelayanan, $this->verlapData($service), 'Kondisi lapangan sesuai pengajuan.');

        if ($stage === 'selesai_bidang') {
            $this->flow->finish($pelayanan, $this->formData($service), 'Pengajuan diselesaikan bidang.');
        }

        if ($stage === 'selesai_verlap') {
            $this->flow->finish($pelayanan, $this->formData($service), 'Pengajuan diselesaikan setelah verifikasi lapangan.');
        }
    }

    /**
     * Geser seluruh timestamp ke tanggal demo tanpa mengubah urutan antar langkah.
     */
    protected function backdate(Pendaftaran $pendaftaran, Carbon $base): void
    {
        $delta = $base->getTimestamp() - $pendaftaran->created_at->getTimestamp();
        $shift = fn (?Carbon $time) => $time?->copy()->addSeconds($delta);

        $pendaftaran->forceFill([
            'created_at' => $base,
            'updated_at' => $base,
            'forwarded_at' => $shift($pendaftaran->forwarded_at),
            'completed_at' => $shift($pendaftaran->completed_at),
            'expires_at' => $shift($pendaftaran->expires_at),
        ])->saveQuietly();

        if ($pelayanan = $pendaftaran->pelayananBidang) {
            $created = $shift($pelayanan->created_at);

            $pelayanan->forceFill([
                'created_at' => $created,
                'updated_at' => $created,
                'scheduled_at' => $shift($pelayanan->scheduled_at)?->toDateString(),
                'verlap_at' => $shift($pelayanan->verlap_at),
                'completed_at' => $shift($pelayanan->completed_at),
            ])->saveQuietly();
        }

        foreach ($pendaftaran->timelines as $timeline) {
            $time = $shift($timeline->created_at);

            $timeline->forceFill(['created_at' => $time, 'updated_at' => $time])->saveQuietly();
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(Service $service): array
    {
        return $this->fill($service->form?->schema['fields'] ?? [], [
            'keperluan' => 'Pengurusan berkas bantuan sosial',
            'desil' => '3',
            'catatan' => 'Penghasilan keluarga menurun sejak awal tahun.',
            'jenis_kks' => 'Rusak',
            'keterangan_desil' => 'Desil 3',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function verlapData(Service $service): array
    {
        return $this->fill($service->form?->schema['verifikasi'] ?? [], [
            'kondisi_rumah' => 'Rumah semi permanen, atap seng, lantai plester.',
            'jumlah_anggota' => 4,
            'catatan_lapangan' => 'Kondisi sesuai pengajuan, tidak ada anggota keluarga lain.',
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    protected function fill(array $fields, array $values): array
    {
        $data = [];

        foreach ($fields as $field) {
            $data[$field['key']] = $values[$field['key']] ?? 'Data contoh';
        }

        return $data;
    }

    protected function dashboardPassword(): ?string
    {
        if (Setting::get('dashboard_password') !== null) {
            return null;
        }

        Setting::setPassword(self::DASHBOARD_PASSWORD);

        return self::DASHBOARD_PASSWORD;
    }

    /**
     * Satu keluarga = satu kartu keluarga; indeks warga pada 'person' mengikuti
     * urutan datar hasil citizens().
     *
     * @return array<int, array<string, mixed>>
     */
    protected function familyCards(): array
    {
        return [
            [
                'nomor_kk' => '3502010000000001',
                'district_code' => '3502010',
                'district_name' => 'Ngrayun',
                'village_code' => '3502010001',
                'village_name' => 'Baosankidul',
                'alamat' => 'Dusun Krajan RT 01 RW 02',
                'anggota' => [
                    ['nama' => 'Sutrisno', 'nik' => '3502010101700001', 'phone' => '081234500001'],
                    ['nama' => 'Sriyati', 'nik' => '3502014503750002'],
                    ['nama' => 'Bagas Prakoso', 'nik' => '3502011209050003'],
                ],
            ],
            [
                'nomor_kk' => '3502020000000002',
                'district_code' => '3502020',
                'district_name' => 'Slahung',
                'village_code' => '3502020001',
                'village_name' => 'Tugurejo',
                'alamat' => 'Dusun Tugurejo RT 03 RW 01',
                'anggota' => [
                    ['nama' => 'Mujiono', 'nik' => '3502020202680001', 'phone' => '081234500002'],
                    ['nama' => 'Ratinah', 'nik' => '3502025504700002'],
                    ['nama' => 'Dewi Anggraini', 'nik' => '3502020106010003'],
                ],
            ],
            [
                'nomor_kk' => '3502030000000003',
                'district_code' => '3502030',
                'district_name' => 'Bungkal',
                'village_code' => '3502030002',
                'village_name' => 'Koripan',
                'alamat' => 'Dusun Koripan RT 02 RW 04',
                'anggota' => [
                    ['nama' => 'Parmin', 'nik' => '3502031508650001', 'phone' => '081234500003'],
                    ['nama' => 'Sukati', 'nik' => '3502030203670002'],
                    ['nama' => 'Rizky Ramadhan', 'nik' => '3502032802030003'],
                ],
            ],
            [
                'nomor_kk' => '3502040000000004',
                'district_code' => '3502040',
                'district_name' => 'Sambit',
                'village_code' => '3502040001',
                'village_name' => 'Gajah',
                'alamat' => 'Dusun Gajah RT 05 RW 02',
                'anggota' => [
                    ['nama' => 'Warsini', 'nik' => '3502041107730001', 'phone' => '081234500004'],
                    ['nama' => 'Sugeng Riyadi', 'nik' => '3502042109990002'],
                ],
            ],
            [
                'nomor_kk' => '3502050000000005',
                'district_code' => '3502050',
                'district_name' => 'Sawoo',
                'village_code' => '3502050002',
                'village_name' => 'Pangkal',
                'alamat' => 'Dusun Pangkal RT 01 RW 03',
                'anggota' => [
                    ['nama' => 'Tuminah', 'nik' => '3502050805620001', 'phone' => '081234500005'],
                    ['nama' => 'Joko Susilo', 'nik' => '3502051707900002'],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function cases(): array
    {
        return [
            [
                'person' => 0,
                'service' => 'pelayanan-informasi-konsultasi-bantuan-sosial',
                'keperluan' => 'Konsultasi syarat bantuan sosial',
                'stage' => 'desk',
                'days_ago' => 24,
                'suffix' => 'DA02',
            ],
            [
                'person' => 1,
                'service' => 'usulan-pembaruan-dtsen',
                'keperluan' => 'Pembaruan data desil DTSEN',
                'stage' => 'antrean',
                'days_ago' => 12,
                'suffix' => 'DB03',
            ],
            [
                'person' => 3,
                'service' => 'fasilitasi-usulan-pkh',
                'keperluan' => 'Usulan menjadi peserta PKH',
                'stage' => 'dijadwalkan',
                'days_ago' => 9,
                'suffix' => 'DC04',
            ],
            [
                'person' => 4,
                'service' => 'bantuan-pangan-non-tunai',
                'keperluan' => 'Penerima BPNT belum menerima saldo',
                'stage' => 'verlap_selesai',
                'days_ago' => 7,
                'suffix' => 'DD05',
            ],
            [
                'person' => 6,
                'service' => 'pbi-jkn',
                'keperluan' => 'Pendaftaran PBI JKN',
                'stage' => 'selesai_verlap',
                'days_ago' => 5,
                'suffix' => 'DE06',
            ],
            [
                'person' => 7,
                'service' => 'rekomendasi-biakesmaskin',
                'keperluan' => 'Rekomendasi biaya pengobatan',
                'stage' => 'selesai_bidang',
                'days_ago' => 3,
                'suffix' => 'DF07',
            ],
            [
                'person' => 9,
                'service' => 'penerbitan-surat-keterangan-kks',
                'keperluan' => 'KKS rusak',
                'stage' => 'desk',
                'days_ago' => 0,
                'suffix' => 'DG08',
            ],
            [
                'person' => 11,
                'service' => 'rekomendasi-baznas',
                'keperluan' => 'Rekomendasi bantuan BAZNAS',
                'stage' => 'baru',
                'days_ago' => 0,
                'suffix' => 'DH09',
            ],
        ];
    }
}
