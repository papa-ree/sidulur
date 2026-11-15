<?php

namespace Bale\Sidulur\Livewire\Pages\Citizen;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Sidulur\Models\Citizen;
use Bale\Sidulur\SidulurPermissions;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('cms::layouts.app')]
#[Title('Detail Warga')]
class Show extends Component
{
    public Citizen $citizen;

    public function mount(Citizen $citizen): void
    {
        TenantConnectionService::ensureActive();
        abort_unless(auth()->user()->can(SidulurPermissions::CITIZEN_VIEW), 403);

        $this->citizen = $citizen->load(['familyCard.citizens', 'documents', 'pendaftarans.service']);
    }

    public function render()
    {
        return view('sidulur::livewire.pages.citizen.show');
    }
}
