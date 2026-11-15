<?php

namespace Bale\Sidulur\Livewire\Pages\Bidang;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Sidulur\Models\Bidang;
use Bale\Sidulur\SidulurPermissions;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('cms::layouts.app')]
#[Title('Form Bidang')]
class Form extends Component
{
    public ?string $bidangId = null;

    public string $nama = '';

    public string $deskripsi = '';

    public string $icon = 'landmark';

    public bool $is_active = true;

    public function mount(?string $bidang = null): void
    {
        TenantConnectionService::ensureActive();
        abort_unless(auth()->user()->can($bidang ? SidulurPermissions::BIDANG_UPDATE : SidulurPermissions::BIDANG_CREATE), 403);

        if (! $bidang) {
            return;
        }

        $record = Bidang::query()->findOrFail($bidang);

        $this->bidangId = $record->getKey();
        $this->nama = $record->nama;
        $this->deskripsi = (string) $record->deskripsi;
        $this->icon = $record->icon;
        $this->is_active = $record->is_active;
    }

    public function save(): void
    {
        // Aksi Livewire berjalan di proses baru: mount() tidak dijalankan lagi.
        TenantConnectionService::ensureActive();
        abort_unless(auth()->user()->can($this->bidangId ? SidulurPermissions::BIDANG_UPDATE : SidulurPermissions::BIDANG_CREATE), 403);

        $data = $this->validate([
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'icon' => ['required', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        Bidang::query()->updateOrCreate(['id' => $this->bidangId], $data);

        $this->dispatch('toast', message: 'Bidang disimpan.', type: 'success');
        $this->redirect(route('sidulur.bidang.index'), navigate: true);
    }

    public function render()
    {
        return view('sidulur::livewire.pages.bidang.form');
    }
}
