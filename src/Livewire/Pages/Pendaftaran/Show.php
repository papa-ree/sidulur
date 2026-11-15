<?php

namespace Bale\Sidulur\Livewire\Pages\Pendaftaran;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Sidulur\Models\Pendaftaran;
use Bale\Sidulur\SidulurPermissions;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('cms::layouts.app')]
#[Title('Detail Pendaftaran')]
class Show extends Component
{
    public Pendaftaran $pendaftaran;

    public function mount(Pendaftaran $pendaftaran): void
    {
        TenantConnectionService::ensureActive();
        abort_unless(auth()->user()->can(SidulurPermissions::PENDAFTARAN_VIEW), 403);

        $this->pendaftaran = $pendaftaran->load([
            'citizen.familyCard',
            'service.bidang',
            'pelayananBidang',
            'timelines',
        ]);
    }

    public function render()
    {
        return view('sidulur::livewire.pages.pendaftaran.show');
    }
}
