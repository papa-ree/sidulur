<?php

namespace Bale\Sidulur\Livewire\Pages\Citizen;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Core\Models\Region;
use Bale\Sidulur\Models\Citizen;
use Bale\Sidulur\Services\CitizenRegistrar;
use Bale\Sidulur\SidulurPermissions;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('cms::layouts.app')]
#[Title('Form Warga')]
class Form extends Component
{
    use WithFileUploads;

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
    }

    public function updatedDistrictCode(): void
    {
        $this->village_code = '';
    }

    public function addAnggota(): void
    {
        $this->anggota[] = ['key' => (string) Str::uuid(), 'nik' => '', 'nama' => '', 'ktp' => null, 'kk' => null];
    }

    public function removeAnggota(int $index): void
    {
        unset($this->anggota[$index]);
        $this->anggota = array_values($this->anggota);
    }

    public function save(CitizenRegistrar $registrar)
    {
        TenantConnectionService::ensureActive();

        // Baris yang tidak diisi sama sekali tidak perlu divalidasi.
        $this->anggota = array_values(array_filter($this->anggota, fn (array $row) => filled($row['nik'] ?? null)
            || filled($row['nama'] ?? null)
            || ($row['ktp'] ?? null)
            || ($row['kk'] ?? null)));

        $data = $this->validate([
            'nama' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'digits:16'],
            'phone' => ['nullable', 'string', 'max:20'],
            'nomor_kk' => ['required', 'digits:16'],
            'district_code' => ['required', 'string'],
            'village_code' => ['required', 'string'],
            'alamat' => ['required', 'string', 'max:500'],
            'ktp' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'kk' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
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
