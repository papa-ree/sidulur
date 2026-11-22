<?php

namespace Bale\Sidulur\Livewire\Pages\Pelayanan;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Sidulur\SidulurPermissions;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('cms::layouts.app')]
#[Title('Pengajuan Selesai')]
class Selesai extends Component
{
    public function mount(): void
    {
        TenantConnectionService::ensureActive();
        abort_unless(auth()->user()->can(SidulurPermissions::PELAYANAN_VIEW), 403);
    }

    public function render()
    {
        return view('sidulur::livewire.pages.pelayanan.selesai');
    }
}
