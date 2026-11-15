<?php

namespace Bale\Sidulur\Livewire\Pages\Pelayanan;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Sidulur\Models\PelayananBidang;
use Bale\Sidulur\Services\OfficerScope;
use Bale\Sidulur\SidulurPermissions;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('cms::layouts.app')]
#[Title('Pelayanan Bidang')]
class Index extends Component
{
    public function mount(): void
    {
        TenantConnectionService::ensureActive();
        abort_unless(auth()->user()->can(SidulurPermissions::PELAYANAN_VIEW), 403);
    }

    public function render()
    {
        $bidangIds = OfficerScope::bidangIds();

        $base = PelayananBidang::query()
            ->with(['pendaftaran.citizen', 'pendaftaran.service', 'bidang'])
            ->when($bidangIds !== null, fn ($q) => $q->whereIn('bidang_id', $bidangIds));

        return view('sidulur::livewire.pages.pelayanan.index', [
            'queue' => (clone $base)
                ->whereIn('status', [PelayananBidang::STATUS_BARU, PelayananBidang::STATUS_VERLAP_SELESAI])
                ->orderBy('created_at')
                ->get(),
            'history' => (clone $base)
                ->whereIn('status', [PelayananBidang::STATUS_DIJADWALKAN, PelayananBidang::STATUS_SELESAI])
                ->latest()
                ->limit(25)
                ->get(),
        ]);
    }
}
