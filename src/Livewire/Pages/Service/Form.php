<?php

namespace Bale\Sidulur\Livewire\Pages\Service;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Sidulur\Models\Bidang;
use Bale\Sidulur\Models\Service;
use Bale\Sidulur\SidulurPermissions;
use Bale\Sidulur\Support\FormSchema;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('cms::layouts.app')]
#[Title('Form Layanan')]
class Form extends Component
{
    public ?string $serviceId = null;

    public string $bidang_id = '';

    public string $nama = '';

    public string $slug = '';

    public string $icon = 'clipboard-list';

    public string $deskripsi = '';

    public bool $selesai_di_pendaftaran = false;

    public bool $butuh_verifikasi = false;

    public ?int $sla_value = null;

    public string $sla_unit = 'hari';

    public bool $is_active = true;

    public int $sort = 0;

    public function mount(?string $service = null): void
    {
        TenantConnectionService::ensureActive();
        abort_unless(auth()->user()->can($service ? SidulurPermissions::SERVICE_UPDATE : SidulurPermissions::SERVICE_CREATE), 403);

        if (! $service) {
            return;
        }

        $record = Service::query()->findOrFail($service);

        $this->serviceId = $record->getKey();
        $this->bidang_id = $record->bidang_id;
        $this->nama = $record->nama;
        $this->slug = $record->slug;
        $this->icon = $record->icon;
        $this->deskripsi = (string) $record->deskripsi;
        $this->selesai_di_pendaftaran = $record->selesai_di_pendaftaran;
        $this->butuh_verifikasi = $record->butuh_verifikasi;
        $this->sla_value = $record->sla_value;
        $this->sla_unit = $record->sla_unit ?: 'hari';
        $this->is_active = $record->is_active;
        $this->sort = $record->sort;
    }

    public function updatedNama(string $value): void
    {
        if (! $this->serviceId) {
            $this->slug = Str::slug($value);
        }
    }

    public function save(): void
    {
        // Aksi Livewire berjalan di proses baru: mount() tidak dijalankan lagi.
        TenantConnectionService::ensureActive();
        abort_unless(auth()->user()->can($this->serviceId ? SidulurPermissions::SERVICE_UPDATE : SidulurPermissions::SERVICE_CREATE), 403);

        $data = $this->validate([
            // Tabel sidulur ada di DB tenant; aturan exists: default memakai
            // koneksi landlord sehingga selalu gagal.
            'bidang_id' => ['required', 'uuid', 'exists:'.TenantConnectionService::connection().'.sidulur_bidangs,id'],
            'nama' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash'],
            'icon' => ['required', 'string', 'max:50'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'selesai_di_pendaftaran' => ['boolean'],
            'butuh_verifikasi' => ['boolean'],
            'sla_value' => ['nullable', 'integer', 'min:1'],
            'sla_unit' => ['required', 'in:jam,hari'],
            'is_active' => ['boolean'],
            'sort' => ['integer', 'min:0'],
        ]);

        $service = Service::query()->updateOrCreate(['id' => $this->serviceId], $data);

        if (! $service->form()->exists()) {
            $service->form()->create([
                'nama' => 'Formulir '.$service->nama,
                'schema' => FormSchema::normalize(null),
            ]);
        }

        $this->dispatch('toast', message: 'Layanan disimpan.', type: 'success');
        $this->redirect(route('sidulur.service.index'), navigate: true);
    }

    public function render()
    {
        return view('sidulur::livewire.pages.service.form', [
            'bidangs' => Bidang::query()->where('is_active', true)->orderBy('nama')->get(),
        ]);
    }
}
