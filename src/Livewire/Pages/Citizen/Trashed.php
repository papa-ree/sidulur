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
#[Title('Data Warga Terhapus')]
class Trashed extends Component
{
    public function mount(): void
    {
        TenantConnectionService::ensureActive();
        abort_unless(auth()->user()->can(SidulurPermissions::CITIZEN_DELETE), 403);
    }

    #[On('restoreCitizen')]
    public function restoreCitizen(string $id): void
    {
        abort_unless(auth()->user()->can(SidulurPermissions::CITIZEN_DELETE), 403);

        TenantConnectionService::ensureActive();

        $citizen = Citizen::onlyTrashed()->find($id);

        if (! $citizen) {
            return;
        }

        $citizen->restore();
        $this->dispatch('refresh');
        $this->dispatch('toast', message: 'Data warga dipulihkan.', type: 'success');
    }

    public function render()
    {
        return view('sidulur::livewire.pages.citizen.trashed');
    }
}
