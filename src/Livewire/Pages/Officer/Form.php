<?php

namespace Bale\Sidulur\Livewire\Pages\Officer;

use Bale\Cms\Models\User;
use Bale\Cms\Services\TenantConnectionService;
use Bale\Sidulur\Models\Bidang;
use Bale\Sidulur\Models\Officer;
use Bale\Sidulur\SidulurPermissions;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('cms::layouts.app')]
#[Title('Form Petugas')]
class Form extends Component
{
    public ?string $officerId = null;

    public string $user_id = '';

    public bool $is_pendaftaran = false;

    public bool $is_bidang = false;

    public bool $is_lapangan = false;

    public bool $is_active = true;

    public array $bidang_ids = [];

    public function mount(?string $officer = null): void
    {
        TenantConnectionService::ensureActive();
        abort_unless(auth()->user()->can($officer ? SidulurPermissions::OFFICER_UPDATE : SidulurPermissions::OFFICER_CREATE), 403);

        if (! $officer) {
            return;
        }

        $record = Officer::query()->with('bidangs')->findOrFail($officer);

        $this->officerId = $record->getKey();
        $this->user_id = $record->user_id;
        $this->is_pendaftaran = $record->is_pendaftaran;
        $this->is_bidang = $record->is_bidang;
        $this->is_lapangan = $record->is_lapangan;
        $this->is_active = $record->is_active;
        $this->bidang_ids = $record->bidangs->pluck('id')->all();
    }

    public function save(): void
    {
        // Aksi Livewire berjalan di proses baru: mount() tidak dijalankan lagi.
        TenantConnectionService::ensureActive();
        abort_unless(auth()->user()->can($this->officerId ? SidulurPermissions::OFFICER_UPDATE : SidulurPermissions::OFFICER_CREATE), 403);

        $data = $this->validate([
            'user_id' => ['required', 'uuid', 'exists:'.TenantConnectionService::connection().'.users,uuid'],
            'is_pendaftaran' => ['boolean'],
            'is_bidang' => ['boolean'],
            'is_lapangan' => ['boolean'],
            'is_active' => ['boolean'],
            'bidang_ids' => ['array'],
            'bidang_ids.*' => ['uuid', 'exists:'.TenantConnectionService::connection().'.sidulur_bidangs,id'],
        ]);

        if (($data['is_bidang'] || $data['is_lapangan']) && $data['bidang_ids'] === []) {
            $this->addError('bidang_ids', 'Petugas bidang/lapangan wajib memiliki minimal satu bidang.');

            return;
        }

        $officer = Officer::query()->updateOrCreate(
            ['id' => $this->officerId],
            [
                'user_id' => $data['user_id'],
                'is_pendaftaran' => $data['is_pendaftaran'],
                'is_bidang' => $data['is_bidang'],
                'is_lapangan' => $data['is_lapangan'],
                'is_active' => $data['is_active'],
            ]
        );

        $officer->bidangs()->sync($data['bidang_ids']);

        $this->dispatch('toast', message: 'Petugas disimpan.', type: 'success');
        $this->redirect(route('sidulur.officer.index'), navigate: true);
    }

    public function render()
    {
        return view('sidulur::livewire.pages.officer.form', [
            // Root sudah melihat semua data, jadi tidak perlu dijadikan petugas.
            'users' => User::query()->where('role', '!=', 'root')->orderBy('name')->get(['uuid', 'name', 'email']),
            'bidangs' => Bidang::query()->where('is_active', true)->orderBy('nama')->get(),
        ]);
    }
}
