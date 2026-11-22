<?php

namespace Bale\Sidulur\Commands;

use Bale\Cms\Models\BaleList;
use Bale\Cms\Services\TenantManager;
use Bale\Sidulur\Models\Bidang;
use Bale\Sidulur\Models\Form;
use Bale\Sidulur\Models\Service;
use Bale\Sidulur\Models\Setting;
use Bale\Sidulur\Support\FormSchema;
use Illuminate\Console\Command;
use Throwable;

class SeedSidulur extends Command
{
    protected $signature = 'sidulur:seed {--tenant= : Slug tenant yang akan di-seed}';

    protected $description = 'Seed bidang, layanan, formulir, dan pengaturan awal Sidulur (idempotent)';

    public function handle(): int
    {
        $tenantSlug = $this->option('tenant');

        if (! $tenantSlug) {
            $tenants = BaleList::all();

            if ($tenants->isEmpty()) {
                $this->error('Tidak ada tenant pada tabel bale_lists.');

                return self::FAILURE;
            }

            $tenantSlug = $this->choice(
                'Database tenant mana yang akan diisi data awal?',
                $tenants->pluck('slug')->toArray()
            );
        }

        try {
            $tenant = BaleList::where('slug', $tenantSlug)->firstOrFail();

            $this->info("Menyiapkan koneksi untuk tenant: {$tenant->slug}");
            TenantManager::initializeFromBaleUuid($tenant->id);

            if (! TenantManager::getActiveConnection()) {
                throw new \Exception("Gagal mengaktifkan koneksi untuk tenant {$tenant->slug}");
            }

            $this->info("Mengisi data awal Sidulur (DB: {$tenant->database_name})...");

            $bidangs = [];
            $count = 0;

            foreach ($this->bidangs() as $bidang) {
                $bidangs[$bidang['nama']] = Bidang::query()->firstOrCreate(
                    ['nama' => $bidang['nama']],
                    ['icon' => $bidang['icon']]
                );
            }

            foreach ($this->services() as $row) {
                $service = Service::query()->firstOrCreate(
                    ['slug' => $row['slug']],
                    [
                        'bidang_id' => $bidangs[$row['bidang']]->getKey(),
                        'nama' => $row['nama'],
                        'icon' => $row['icon'],
                        'selesai_di_pendaftaran' => $row['selesai_di_pendaftaran'],
                        'butuh_verifikasi' => $row['butuh_verifikasi'],
                        'sla_value' => $row['sla_value'],
                        'sla_unit' => 'hari',
                        'sort' => $row['sort'],
                    ]
                );

                Form::query()->firstOrCreate(
                    ['service_id' => $service->getKey()],
                    [
                        'nama' => 'Formulir '.$service->nama,
                        'schema' => FormSchema::normalize([
                            'persyaratan' => $row['persyaratan'],
                            'fields' => $row['fields'],
                            'verifikasi' => $row['butuh_verifikasi'] ? $this->verlapFields() : [],
                        ]),
                    ]
                );

                $count++;
            }

            $settings = $this->seedSettings();

            $this->info("{$count} layanan dan {$settings} pengaturan terisi (data lama tidak ditimpa).");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Pengisian data gagal: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * @return array<int, array{nama: string, icon: string}>
     */
    protected function bidangs(): array
    {
        return [
            ['nama' => 'Pelayanan Penanganan Fakir Miskin dan Pemberdayaan Sosial', 'icon' => 'hand-heart'],
            ['nama' => 'Pelayanan Perlindungan Jaminan Sosial', 'icon' => 'shield-check'],
        ];
    }

    /**
     * Data mengikuti docs/sidulur/layanan-bidang.md.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function services(): array
    {
        $fakirMiskin = 'Pelayanan Penanganan Fakir Miskin dan Pemberdayaan Sosial';
        $jaminanSosial = 'Pelayanan Perlindungan Jaminan Sosial';

        return [
            [
                'bidang' => $fakirMiskin,
                'nama' => 'Pelayanan Informasi atau Konsultasi Bantuan Sosial',
                'slug' => 'pelayanan-informasi-konsultasi-bantuan-sosial',
                'icon' => 'message-circle',
                'selesai_di_pendaftaran' => true,
                'butuh_verifikasi' => false,
                'sla_value' => 1,
                'sort' => 1,
                'persyaratan' => $this->requirements(),
                'fields' => [
                    ['key' => 'keperluan', 'label' => 'Keperluan', 'type' => 'textarea', 'required' => true],
                ],
            ],
            [
                'bidang' => $fakirMiskin,
                'nama' => 'Usulan Pembaruan Data Tunggal Sosial Ekonomi Nasional (DTSEN)',
                'slug' => 'usulan-pembaruan-dtsen',
                'icon' => 'database',
                'selesai_di_pendaftaran' => false,
                'butuh_verifikasi' => true,
                'sla_value' => 7,
                'sort' => 2,
                'persyaratan' => $this->requirements(),
                'fields' => [
                    ['key' => 'desil', 'label' => 'Desil', 'type' => 'number', 'required' => true],
                    ['key' => 'catatan', 'label' => 'Data yang perlu diperbarui', 'type' => 'textarea'],
                ],
            ],
            [
                'bidang' => $fakirMiskin,
                'nama' => 'Penerbitan Surat Keterangan DTSEN',
                'slug' => 'penerbitan-surat-keterangan-dtsen',
                'icon' => 'file-text',
                'selesai_di_pendaftaran' => true,
                'butuh_verifikasi' => false,
                'sla_value' => 1,
                'sort' => 3,
                'persyaratan' => $this->requirements(),
                'fields' => [
                    ['key' => 'keperluan', 'label' => 'Keperluan surat', 'type' => 'textarea', 'required' => true],
                ],
            ],
            [
                'bidang' => $fakirMiskin,
                'nama' => 'Fasilitasi Usulan Program Keluarga Harapan (PKH)',
                'slug' => 'fasilitasi-usulan-pkh',
                'icon' => 'hand-coins',
                'selesai_di_pendaftaran' => false,
                'butuh_verifikasi' => true,
                'sla_value' => 7,
                'sort' => 4,
                'persyaratan' => $this->requirements(),
                'fields' => $this->desilField(4),
            ],
            [
                'bidang' => $fakirMiskin,
                'nama' => 'Bantuan Pangan Non Tunai (BPNT)',
                'slug' => 'bantuan-pangan-non-tunai',
                'icon' => 'shopping-basket',
                'selesai_di_pendaftaran' => false,
                'butuh_verifikasi' => true,
                'sla_value' => 7,
                'sort' => 5,
                'persyaratan' => $this->requirements(),
                'fields' => $this->desilField(4),
            ],
            [
                'bidang' => $fakirMiskin,
                'nama' => 'PBI JKN',
                'slug' => 'pbi-jkn',
                'icon' => 'heart-pulse',
                'selesai_di_pendaftaran' => false,
                'butuh_verifikasi' => true,
                'sla_value' => 7,
                'sort' => 6,
                'persyaratan' => $this->requirements(),
                'fields' => $this->desilField(5),
            ],
            [
                'bidang' => $jaminanSosial,
                'nama' => 'Pelayanan PBI Daerah/PBPU-Pemda',
                'slug' => 'pelayanan-pbi-daerah',
                'icon' => 'hospital',
                'selesai_di_pendaftaran' => false,
                'butuh_verifikasi' => true,
                'sla_value' => 7,
                'sort' => 7,
                'persyaratan' => $this->requirements(['Surat Keterangan Tidak Mampu dari Desa/Kelurahan', 'Surat Keterangan Diagnosa dari Dokter']),
                'fields' => $this->desilField(5),
            ],
            [
                'bidang' => $fakirMiskin,
                'nama' => 'Penerbitan Surat Keterangan KKS Hilang/Rusak/Terblokir',
                'slug' => 'penerbitan-surat-keterangan-kks',
                'icon' => 'credit-card',
                'selesai_di_pendaftaran' => true,
                'butuh_verifikasi' => false,
                'sla_value' => 1,
                'sort' => 8,
                'persyaratan' => $this->requirements(['Surat Kehilangan dari Kepolisian (bila hilang)']),
                'fields' => [
                    [
                        'key' => 'jenis_kks',
                        'label' => 'Jenis pengajuan KKS',
                        'type' => 'select',
                        'required' => true,
                        'options' => ['Hilang', 'Rusak', 'Terblokir', 'Tertelan', 'Kadaluarsa'],
                    ],
                ],
            ],
            [
                'bidang' => $jaminanSosial,
                'nama' => 'Penerbitan Rekomendasi Bantuan Biaya Kesehatan Masyarakat Miskin (BIAKESMASKIN)',
                'slug' => 'rekomendasi-biakesmaskin',
                'icon' => 'stethoscope',
                'selesai_di_pendaftaran' => false,
                'butuh_verifikasi' => true,
                'sla_value' => 7,
                'sort' => 9,
                'persyaratan' => $this->requirements([
                    'Surat Permohonan Rekomendasi dari Pemerintah Desa',
                    'Surat Keterangan Tidak Mampu dari Desa/Kelurahan',
                    'Surat Pernyataan dari Masyarakat',
                    'Surat Diagnosa Sakit dan Rujukan dari Faskes',
                ]),
                'fields' => [
                    ['key' => 'keterangan_desil', 'label' => 'Keterangan desil', 'type' => 'text', 'required' => true],
                ],
            ],
            [
                'bidang' => $jaminanSosial,
                'nama' => 'Rekomendasi Bantuan Biaya Pengobatan ke BAZNAS',
                'slug' => 'rekomendasi-baznas',
                'icon' => 'hand-heart',
                'selesai_di_pendaftaran' => false,
                'butuh_verifikasi' => true,
                'sla_value' => 7,
                'sort' => 10,
                'persyaratan' => $this->requirements([
                    'Surat Permohonan Rekomendasi',
                    'Surat Keterangan Tidak Mampu dari Desa/Kelurahan',
                    'Foto Rumah',
                    'Foto Pasien',
                ]),
                'fields' => [
                    ['key' => 'keterangan_desil', 'label' => 'Keterangan desil', 'type' => 'text', 'required' => true],
                ],
            ],
        ];
    }

    /**
     * Syarat tambahan saja: identitas & berkas warga diambil otomatis dari Data Warga.
     *
     * @param  array<int, string>  $extra
     * @return array<int, array<string, mixed>>
     */
    protected function requirements(array $extra = []): array
    {
        $rows = [];

        foreach ($extra as $index => $label) {
            $rows[] = [
                'kode' => 'dokumen_'.($index + 1),
                'nama' => $label,
                'wajib' => true,
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function desilField(int $max): array
    {
        return [
            [
                'key' => 'desil',
                'label' => 'Desil (1-'.$max.')',
                'type' => 'select',
                'required' => true,
                'options' => array_map('strval', range(1, $max)),
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function verlapFields(): array
    {
        return [
            ['key' => 'kondisi_rumah', 'label' => 'Kondisi rumah', 'type' => 'textarea', 'required' => true],
            ['key' => 'jumlah_anggota', 'label' => 'Jumlah anggota keluarga', 'type' => 'number'],
            ['key' => 'catatan_lapangan', 'label' => 'Catatan lapangan', 'type' => 'textarea'],
        ];
    }

    protected function seedSettings(): int
    {
        $defaults = [
            'ticket_expiry_days' => '30',
            'document_link_days' => '30',
            'whatsapp_center' => (string) config('sidulur.whatsapp.center'),
        ];

        $count = 0;

        foreach ($defaults as $key => $value) {
            if (Setting::get($key) !== null || $value === '') {
                continue;
            }

            Setting::put($key, $value);
            $count++;
        }

        return $count;
    }
}
