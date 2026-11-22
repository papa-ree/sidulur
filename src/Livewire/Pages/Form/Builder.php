<?php

namespace Bale\Sidulur\Livewire\Pages\Form;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Sidulur\Models\Form;
use Bale\Sidulur\Models\Service;
use Bale\Sidulur\SidulurPermissions;
use Bale\Sidulur\Support\FormSchema;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('cms::layouts.app')]
#[Title('Bangun Formulir')]
class Builder extends Component
{
    public string $serviceId = '';

    public string $nama = '';

    public array $schema = ['persyaratan' => [], 'fields' => [], 'verifikasi' => []];

    public int $version = 1;

    public bool $showPreview = false;

    public array $previewData = [];

    /**
     * Opsi per field disimpan sebagai teks berkoma agar bisa di-`wire:model`
     * tanpa request per ketikan; dikonversi ke array saat simpan.
     */
    public array $optionsText = [];

    public function mount(Service $service): void
    {
        TenantConnectionService::ensureActive();
        abort_unless(auth()->user()->can(SidulurPermissions::FORM_VIEW), 403);

        $form = $service->form;

        if (! $form) {
            // Membuat formulir adalah aksi tulis: penonton tidak boleh memicunya.
            abort_unless(auth()->user()->can(SidulurPermissions::FORM_UPDATE), 403);

            $form = $service->form()->create([
                'nama' => 'Formulir '.$service->nama,
                'schema' => FormSchema::normalize(null),
            ]);
        }

        $this->serviceId = $service->getKey();
        $this->nama = $form->nama;
        $this->schema = FormSchema::normalize($form->schema);
        $this->version = $form->version;
        $this->syncOptionsText();
    }

    public function addRequirement(): void
    {
        abort_unless(auth()->user()->can(SidulurPermissions::FORM_UPDATE), 403);

        $this->schema['persyaratan'][] = [
            'kode' => 'syarat_'.(count($this->schema['persyaratan']) + 1),
            'nama' => '',
            'keterangan' => '',
            'wajib' => true,
            'urutan' => count($this->schema['persyaratan']) + 1,
            'type' => 'file',
        ];
    }

    public function removeRequirement(int $index): void
    {
        abort_unless(auth()->user()->can(SidulurPermissions::FORM_UPDATE), 403);

        unset($this->schema['persyaratan'][$index]);
        $this->schema['persyaratan'] = array_values($this->schema['persyaratan']);
    }

    public function addField(string $section = 'fields'): void
    {
        abort_unless(auth()->user()->can(SidulurPermissions::FORM_UPDATE), 403);

        $list = $this->schema[$section];
        $list[] = [
            'key' => ($section === 'fields' ? 'field_' : 'verifikasi_').(count($list) + 1),
            'label' => '',
            'type' => 'text',
            'required' => false,
            'placeholder' => '',
            'options' => [],
            'order' => count($list) + 1,
        ];
        $this->schema[$section] = $list;
        $this->optionsText[$section][count($list) - 1] = '';
    }

    public function removeField(string $section, int $index): void
    {
        abort_unless(auth()->user()->can(SidulurPermissions::FORM_UPDATE), 403);

        unset($this->schema[$section][$index]);
        $this->schema[$section] = array_values($this->schema[$section]);
        $this->syncOptionsText();
    }

    /**
     * Kosongkan editor saja; schema lama tetap tersimpan sampai "Simpan" ditekan.
     */
    public function resetForm(): void
    {
        abort_unless(auth()->user()->can(SidulurPermissions::FORM_UPDATE), 403);

        TenantConnectionService::ensureActive();

        $this->schema = FormSchema::normalize(null);
        $this->syncOptionsText();
        $this->dispatch('toast', message: 'Formulir dikosongkan. Simpan untuk menerapkan.', type: 'success');
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can(SidulurPermissions::FORM_UPDATE), 403);

        // Aksi Livewire berjalan di proses baru: mount() tidak dijalankan lagi.
        TenantConnectionService::ensureActive();

        $this->validate([
            'nama' => ['required', 'string', 'max:255'],
        ]);

        $form = Form::query()->where('service_id', $this->serviceId)->firstOrFail();
        $this->applyOptions();

        // Identitas & berkas warga sudah otomatis: syarat manual dengan kode/nama
        // yang sama akan bentrok dengan blok tetap di halaman verifikasi.
        foreach ($this->schema['persyaratan'] as $index => $row) {
            $column = FormSchema::reservedRequirement($row);

            if ($column === null) {
                continue;
            }

            throw ValidationException::withMessages([
                "schema.persyaratan.{$index}.{$column}" => __('Identitas & berkas warga sudah otomatis; syarat ini tidak perlu dibuat manual.'),
            ]);
        }

        $normalized = FormSchema::normalize($this->schema);

        $form->update([
            'nama' => $this->nama,
            'schema' => $normalized,
            'version' => $form->version + 1,
        ]);

        $this->schema = $normalized;
        $this->version = $form->version;
        $this->dispatch('toast', message: 'Formulir disimpan.', type: 'success');
    }

    /**
     * Samakan teks opsi dengan schema (dipanggil setiap schema berubah bentuk).
     */
    protected function syncOptionsText(): void
    {
        $this->optionsText = [];

        foreach (['fields', 'verifikasi'] as $section) {
            foreach ($this->schema[$section] as $index => $row) {
                $this->optionsText[$section][$index] = implode(',', $row['options'] ?? []);
            }
        }
    }

    protected function applyOptions(): void
    {
        foreach (['fields', 'verifikasi'] as $section) {
            foreach ($this->schema[$section] as $index => $row) {
                $this->schema[$section][$index]['options'] = collect(explode(',', $this->optionsText[$section][$index] ?? ''))
                    ->map(fn ($option) => trim($option))
                    ->filter()
                    ->values()
                    ->all();
            }
        }
    }

    public function render()
    {
        return view('sidulur::livewire.pages.form.builder', [
            'activeService' => Service::query()->with('bidang')->findOrFail($this->serviceId),
            'types' => FormSchema::FIELD_TYPES,
            'requirementTypes' => FormSchema::REQUIREMENT_TYPES,
        ]);
    }
}
