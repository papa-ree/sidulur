<?php

namespace Bale\Sidulur\Livewire\Pages\Bidang;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Sidulur\Models\Bidang;
use Bale\Sidulur\SidulurPermissions;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('cms::layouts.app')]
#[Title('Bidang')]
class Index extends Component
{
    public function mount(): void
    {
        TenantConnectionService::ensureActive();
        abort_unless(auth()->user()->can(SidulurPermissions::BIDANG_VIEW), 403);
    }

    #[On('deleteBidang')]
    public function deleteBidang(string $id): void
    {
        abort_unless(auth()->user()->can(SidulurPermissions::BIDANG_DELETE), 403);

        $bidang = Bidang::query()->withCount(['services', 'pelayananBidangs'])->findOrFail($id);

        if ($bidang->services_count > 0 || $bidang->pelayanan_bidangs_count > 0) {
            $this->dispatch('toast', message: 'Bidang masih dipakai layanan atau pengajuan.', type: 'error');

            return;
        }

        $bidang->delete();
        $this->dispatch('refresh');
        $this->dispatch('toast', message: 'Bidang dihapus.', type: 'success');
    }

    public function render()
    {
        return view('sidulur::livewire.pages.bidang.index');
    }
}
