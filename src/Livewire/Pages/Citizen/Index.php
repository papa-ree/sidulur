<?php

namespace Bale\Sidulur\Livewire\Pages\Citizen;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Sidulur\Models\Citizen;
use Bale\Sidulur\SidulurPermissions;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('cms::layouts.app')]
#[Title('Data Warga')]
class Index extends Component
{
    public function mount(): void
    {
        TenantConnectionService::ensureActive();
        abort_unless(auth()->user()->can(SidulurPermissions::CITIZEN_VIEW), 403);
    }

    #[On('deleteCitizen')]
    public function deleteCitizen(string $id): void
    {
        abort_unless(auth()->user()->can(SidulurPermissions::CITIZEN_DELETE), 403);

        $citizen = Citizen::query()->find($id);

        if (! $citizen) {
            return;
        }

        if ($citizen->pendaftarans()->exists()) {
            $this->dispatch('toast', message: 'Warga sudah memiliki pengajuan, tidak dapat dihapus.', type: 'error');

            return;
        }

        $citizen->delete();
        $this->dispatch('refresh');
        $this->dispatch('toast', message: 'Data warga dihapus.', type: 'success');
    }

    public function render()
    {
        return view('sidulur::livewire.pages.citizen.index');
    }
}
