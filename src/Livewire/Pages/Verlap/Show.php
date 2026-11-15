<?php

namespace Bale\Sidulur\Livewire\Pages\Verlap;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Sidulur\Models\PelayananBidang;
use Bale\Sidulur\Services\OfficerScope;
use Bale\Sidulur\Services\SubmissionFlow;
use Bale\Sidulur\SidulurPermissions;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('cms::layouts.app')]
#[Title('Form Verifikasi Lapangan')]
class Show extends Component
{
    public PelayananBidang $pelayanan;

    public array $verlapData = [];

    public string $note = '';

    public function mount(PelayananBidang $pelayanan): void
    {
        TenantConnectionService::ensureActive();
        abort_unless(auth()->user()->can(SidulurPermissions::VERLAP_VIEW), 403);

        $ids = OfficerScope::bidangIds();

        if ($ids !== null && ! in_array($pelayanan->bidang_id, $ids, true)) {
            abort(403);
        }

        $this->pelayanan = $pelayanan->load([
            'pendaftaran.citizen.familyCard',
            'pendaftaran.service',
            'bidang',
        ]);

        $this->verlapData = $pelayanan->verlap_data ?? [];
    }

    public function save(SubmissionFlow $flow)
    {
        abort_unless(auth()->user()->can(SidulurPermissions::VERLAP_UPDATE), 403);

        $this->validate(['verlapData' => ['array']]);

        $flow->recordVerlap($this->pelayanan, $this->verlapData, $this->note ?: null);
        $this->pelayanan->refresh();
        $this->dispatch('toast', message: 'Hasil verifikasi lapangan disimpan.', type: 'success');

        return redirect()->route('sidulur.verlap.index');
    }

    public function render()
    {
        return view('sidulur::livewire.pages.verlap.show');
    }
}
