<?php

namespace Bale\Sidulur\Livewire\Pages\Service;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Sidulur\Models\Service;
use Bale\Sidulur\SidulurPermissions;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('cms::layouts.app')]
#[Title('Layanan')]
class Index extends Component
{
    public function mount(): void
    {
        TenantConnectionService::ensureActive();
        abort_unless(auth()->user()->can(SidulurPermissions::SERVICE_VIEW), 403);
    }

    #[On('deleteService')]
    public function deleteService(string $id): void
    {
        abort_unless(auth()->user()->can(SidulurPermissions::SERVICE_DELETE), 403);

        $service = Service::query()->withCount('pendaftarans')->findOrFail($id);

        if ($service->pendaftarans_count > 0) {
            $this->dispatch('toast', message: 'Layanan sudah memiliki pengajuan.', type: 'error');

            return;
        }

        $service->form()->delete();
        $service->delete();
        $this->dispatch('refresh');
        $this->dispatch('toast', message: 'Layanan dihapus.', type: 'success');
    }

    public function render()
    {
        return view('sidulur::livewire.pages.service.index');
    }
}
