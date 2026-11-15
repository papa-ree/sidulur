<?php

namespace Bale\Sidulur\Livewire\Pages\Form;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Sidulur\Models\Service;
use Bale\Sidulur\SidulurPermissions;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('cms::layouts.app')]
#[Title('Formulir Layanan')]
class Index extends Component
{
    public function mount(): void
    {
        TenantConnectionService::ensureActive();
        abort_unless(auth()->user()->can(SidulurPermissions::FORM_VIEW), 403);
    }

    public function render()
    {
        return view('sidulur::livewire.pages.form.index', [
            'services' => Service::query()->with(['form', 'bidang'])->orderBy('nama')->get(),
        ]);
    }
}
