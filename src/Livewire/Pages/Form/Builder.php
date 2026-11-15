<?php

namespace Bale\Sidulur\Livewire\Pages\Form;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Sidulur\Models\Form;
use Bale\Sidulur\Models\Service;
use Bale\Sidulur\SidulurPermissions;
use Bale\Sidulur\Support\FormSchema;
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

    public function mount(Service $service): void
    {
        TenantConnectionService::ensureActive();
        abort_unless(auth()->user()->can(SidulurPermissions::FORM_VIEW), 403);

        $form = $service->form;

        if (! $form) {
            $form = $service->form()->create([
                'nama' => 'Formulir '.$service->nama,
                'schema' => FormSchema::normalize(null),
            ]);
        }

        $this->serviceId = $service->getKey();
        $this->nama = $form->nama;
        $this->schema = FormSchema::normalize($form->schema);
        $this->version = $form->version;
    }

    public function addRequirement(): void
    {
        $this->schema['persyaratan'][] = [
            'kode' => 'syarat_'.(count($this->schema['persyaratan']) + 1),
            'nama' => '',
            'keterangan' => '',
            'wajib' => true,
            'urutan' => count($this->schema['persyaratan']) + 1,
            'sumber' => '',
        ];
    }

    public function removeRequirement(int $index): void
    {
        unset($this->schema['persyaratan'][$index]);
        $this->schema['persyaratan'] = array_values($this->schema['persyaratan']);
    }

    public function addField(string $section = 'fields'): void
    {
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
    }

    public function removeField(string $section, int $index): void
    {
        unset($this->schema[$section][$index]);
        $this->schema[$section] = array_values($this->schema[$section]);
    }

    public function updateOptions(string $section, int $index, string $value): void
    {
        $this->schema[$section][$index]['options'] = collect(explode(',', $value))
            ->map(fn ($option) => trim($option))
            ->filter()
            ->values()
            ->all();
    }

    public function save(): void
    {
        // Aksi Livewire berjalan di proses baru: mount() tidak dijalankan lagi.
        TenantConnectionService::ensureActive();
        abort_unless(auth()->user()->can(SidulurPermissions::FORM_UPDATE), 403);

        $this->validate([
            'nama' => ['required', 'string', 'max:255'],
        ]);

        $form = Form::query()->where('service_id', $this->serviceId)->firstOrFail();
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

    public function render()
    {
        return view('sidulur::livewire.pages.form.builder', [
            'activeService' => Service::query()->with('bidang')->findOrFail($this->serviceId),
            'types' => FormSchema::FIELD_TYPES,
            'sources' => FormSchema::SOURCES,
        ]);
    }
}
