<?php

namespace Bale\Sidulur\Livewire\Pages\Citizen;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Core\Models\Region;
use Bale\Sidulur\Models\Citizen;
use Bale\Sidulur\Models\FamilyCard;
use Bale\Sidulur\Services\CitizenRegistrar;
use Bale\Sidulur\Services\DocumentService;
use Bale\Sidulur\SidulurPermissions;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('cms::layouts.app')]
#[Title('Form Warga')]
class Form extends Component
{
    use WithFileUploads;

    #[Locked]
    public ?string $citizenId = null;

    public string $nama = '';

    public string $nik = '';

    public string $phone = '';

    public string $nomor_kk = '';

    public string $district_code = '';

    public string $village_code = '';

    public string $alamat = '';

    public $ktp;

    public $kk;

    /**
     * Pratinjau dokumen tersimpan (ktp warga dan kk) saat mengubah data.
     *
     * @var array{ktp: array{name: string, url: string, is_image: bool}|null, kk: array{name: string, url: string, is_image: bool}|null}
     */
    public array $existing = ['ktp' => null, 'kk' => null];

    /**
     * Anggota keluarga baru pada KK yang sama (baris dinamis).
     *
     * @var array<int, array{key: string, nik: string, nama: string, ktp: mixed, kk: mixed}>
     */
    public array $anggota = [];

    public function mount(?string $citizen = null): void
    {
        TenantConnectionService::ensureActive();

        $permission = $citizen ? SidulurPermissions::CITIZEN_UPDATE : SidulurPermissions::CITIZEN_CREATE;
        abort_unless(auth()->user()->can($permission), 403);

        // Slot kosong: Alpine menampilkannya tanpa request, jadi "Tambah" terasa instan.
        $this->anggota = array_map(fn () => $this->blankAnggota(), range(1, 3));

        // Prefill dari panel pemohon Vada; nomornya sudah dinormalisasi `62...`.
        if (! $citizen && request()->filled('phone')) {
            $this->phone = (string) request('phone');
        }

        if (! $citizen) {
            return;
        }

        $record = Citizen::query()->with('familyCard')->findOrFail($citizen);

        $this->citizenId = $record->getKey();
        $this->nama = $record->nama;
        $this->nik = (string) $record->nik;
        $this->phone = (string) $record->phone;
        $this->nomor_kk = (string) $record->familyCard?->nomor_kk;
        $this->district_code = (string) $record->familyCard?->district_code;
        $this->village_code = (string) $record->familyCard?->village_code;
        $this->alamat = (string) $record->familyCard?->alamat;

        $documents = app(DocumentService::class);
        $this->existing = [
            'ktp' => $documents->preview($record->documents->firstWhere('kind', 'ktp')),
            'kk' => $documents->preview($record->familyCard?->documents->firstWhere('kind', 'kk')),
        ];
    }

    public function updatedDistrictCode(): void
    {
        $this->village_code = '';
    }

    /** KK tersimpan pada kartu nomor ini → unggah ulang tidak wajib. */
    public function updatedNomorKk(): void
    {
        $this->existing['kk'] = app(DocumentService::class)
            ->preview(FamilyCard::kkDocument($this->nomor_kk));
    }

    public function addAnggota(): void
    {
        $this->anggota[] = $this->blankAnggota();
    }

    /**
     * @return array{key: string, nik: string, nama: string, ktp: mixed, kk: mixed}
     */
    protected function blankAnggota(): array
    {
        return ['key' => (string) Str::uuid(), 'nik' => '', 'nama' => '', 'ktp' => null, 'kk' => null];
    }

    public function removeAnggota(int $index): void
    {
        unset($this->anggota[$index]);
        $this->anggota = array_values($this->anggota);
    }

    public function save(CitizenRegistrar $registrar)
    {
        $permission = $this->citizenId ? SidulurPermissions::CITIZEN_UPDATE : SidulurPermissions::CITIZEN_CREATE;
        abort_unless(auth()->user()->can($permission), 403);

        TenantConnectionService::ensureActive();

        // x-mask menambahkan spasi; validasi tetap memakai digit murni.
        $this->nik = preg_replace('/\D+/', '', $this->nik);
        $this->nomor_kk = preg_replace('/\D+/', '', $this->nomor_kk);
        $this->phone = preg_replace('/\D+/', '', $this->phone);

        foreach ($this->anggota as $index => $row) {
            $this->anggota[$index]['nik'] = preg_replace('/\D+/', '', (string) ($row['nik'] ?? ''));
        }

        // Baris yang tidak diisi sama sekali tidak perlu divalidasi.
        $this->anggota = array_values(array_filter($this->anggota, fn (array $row) => filled($row['nik'] ?? null)
            || filled($row['nama'] ?? null)
            || ($row['ktp'] ?? null)
            || ($row['kk'] ?? null)));

        $data = $this->validate([
            'nama' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'digits:16'],
            'phone' => ['required', 'string', 'max:20'],
            'nomor_kk' => ['required', 'digits:16'],
            'district_code' => ['required', 'string'],
            'village_code' => ['required', 'string'],
            'alamat' => ['required', 'string', 'max:500'],
            // Berkas identitas wajib juga untuk warga lama; unggah ulang hanya
            // diperlukan bila dokumennya belum pernah tersimpan.
            'ktp' => [$this->existing['ktp'] ? 'nullable' : 'required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'kk' => [$this->existing['kk'] ? 'nullable' : 'required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'anggota' => ['array'],
            'anggota.*.nik' => ['required', 'digits:16', function (string $attribute, $value, \Closure $fail) {
                if ($value === $this->nik) {
                    $fail('NIK anggota tidak boleh sama dengan warga utama.');
                }
            }],
            'anggota.*.nama' => ['required', 'string', 'max:255'],
            'anggota.*.ktp' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'anggota.*.kk' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ]);

        $citizen = $registrar->register(
            $data,
            $this->ktp,
            $this->kk,
            $this->citizenId ? Citizen::query()->findOrFail($this->citizenId) : null,
            $this->anggota
        );

        $this->dispatch('toast', message: 'Data warga disimpan.', type: 'success');

        return redirect()->route('sidulur.citizen.show', $citizen->getKey());
    }

    public function render()
    {
        return view('sidulur::livewire.pages.citizen.form', [
            'districts' => Region::districts()->map(fn ($d) => [
                'value' => $d->district_code,
                'label' => $d->district_name,
            ])->all(),
            'villages' => $this->district_code
                ? Region::villages($this->district_code)->map(fn ($v) => [
                    'value' => $v->village_code,
                    'label' => $v->village_name,
                ])->all()
                : [],
        ]);
    }
}
