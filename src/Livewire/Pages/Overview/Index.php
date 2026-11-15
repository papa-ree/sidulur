<?php

namespace Bale\Sidulur\Livewire\Pages\Overview;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Sidulur\Models\Bidang;
use Bale\Sidulur\Models\Citizen;
use Bale\Sidulur\Models\PelayananBidang;
use Bale\Sidulur\Models\Pendaftaran;
use Bale\Sidulur\Models\Service;
use Bale\Sidulur\Services\OfficerScope;
use Bale\Sidulur\SidulurPermissions;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('cms::layouts.app')]
#[Title('Overview Sidulur')]
class Index extends Component
{
    public function mount(): void
    {
        TenantConnectionService::ensureActive();
        abort_unless(auth()->user()->can(SidulurPermissions::VIEW), 403);
    }

    public function render()
    {
        TenantConnectionService::ensureActive();

        $bidangIds = OfficerScope::bidangIds();

        $queue = PelayananBidang::query()
            ->when($bidangIds !== null, fn ($q) => $q->whereIn('bidang_id', $bidangIds));

        return view('sidulur::livewire.pages.overview.index', [
            'totalPendaftaran' => Pendaftaran::query()->count(),
            'todayPendaftaran' => Pendaftaran::query()->whereDate('created_at', today())->count(),
            'antreanBidang' => (clone $queue)->whereIn('status', [
                PelayananBidang::STATUS_BARU,
                PelayananBidang::STATUS_VERLAP_SELESAI,
            ])->count(),
            'antreanVerlap' => (clone $queue)->where('status', PelayananBidang::STATUS_DIJADWALKAN)->count(),
            'selesai' => (clone $queue)->where('status', PelayananBidang::STATUS_SELESAI)->count(),
            'totalCitizen' => Citizen::query()->count(),
            'totalBidang' => Bidang::query()->where('is_active', true)->count(),
            'totalService' => Service::query()->where('is_active', true)->count(),
            'recent' => Pendaftaran::query()->with(['citizen', 'service'])->latest()->limit(8)->get(),
            'topServices' => Pendaftaran::query()
                ->selectRaw('service_id, count(*) as total')
                ->with('service')
                ->groupBy('service_id')
                ->orderByDesc('total')
                ->limit(5)
                ->get(),
        ]);
    }
}
