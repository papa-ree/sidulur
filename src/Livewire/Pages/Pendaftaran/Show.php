<?php

namespace Bale\Sidulur\Livewire\Pages\Pendaftaran;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Sidulur\Models\Pendaftaran;
use Bale\Sidulur\SidulurPermissions;
use Bale\Sidulur\Support\FormSchema;
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
            'citizen.familyCard.documents',
            'citizen.documents',
            'documents',
            'service.bidang',
            'pelayananBidang',
            'timelines',
        ]);
    }

    public function render()
    {
        $citizen = $this->pendaftaran->citizen;

        return view('sidulur::livewire.pages.pendaftaran.show', [
            'requirements' => FormSchema::normalize($this->pendaftaran->form_snapshot)['persyaratan'],
            'identityMissing' => FormSchema::identityMissing(
                $citizen->documents->where('kind', 'ktp')->isNotEmpty(),
                $citizen->familyCard?->documents->where('kind', 'kk')->isNotEmpty() ?? false,
                filled($citizen->phone),
            ),
            'requirementValues' => $this->pendaftaran->form_data['_persyaratan'] ?? [],
        ]);
    }
}
