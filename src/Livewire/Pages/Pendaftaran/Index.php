<?php

namespace Bale\Sidulur\Livewire\Pages\Pendaftaran;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Sidulur\SidulurPermissions;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('cms::layouts.app')]
#[Title('Pendaftaran')]
class Index extends Component
{
    public function mount(): void
    {
        TenantConnectionService::ensureActive();
        abort_unless(auth()->user()->can(SidulurPermissions::PENDAFTARAN_VIEW), 403);
    }

    public function render()
    {
        return view('sidulur::livewire.pages.pendaftaran.index');
    }
}
