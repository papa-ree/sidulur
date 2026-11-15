<?php

namespace Bale\Sidulur\Livewire\Pages\Officer;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Sidulur\Models\Officer;
use Bale\Sidulur\SidulurPermissions;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('cms::layouts.app')]
#[Title('Petugas')]
class Index extends Component
{
    public function mount(): void
    {
        TenantConnectionService::ensureActive();
        abort_unless(auth()->user()->can(SidulurPermissions::OFFICER_VIEW), 403);
    }

    #[On('deleteOfficer')]
    public function deleteOfficer(string $id): void
    {
        abort_unless(auth()->user()->can(SidulurPermissions::OFFICER_DELETE), 403);

        Officer::query()->whereKey($id)->delete();
        $this->dispatch('refresh');
        $this->dispatch('toast', message: 'Petugas dihapus.', type: 'success');
    }

    public function render()
    {
        return view('sidulur::livewire.pages.officer.index');
    }
}
