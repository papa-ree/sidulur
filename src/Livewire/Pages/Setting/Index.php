<?php

namespace Bale\Sidulur\Livewire\Pages\Setting;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Sidulur\Models\Setting;
use Bale\Sidulur\SidulurPermissions;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('cms::layouts.app')]
#[Title('Pengaturan Sidulur')]
class Index extends Component
{
    public string $password = '';

    public string $password_confirmation = '';

    public ?int $ticket_expiry_days = 30;

    public ?int $document_link_days = 30;

    public string $whatsapp_center = '';

    public function mount(): void
    {
        TenantConnectionService::ensureActive();
        abort_unless(auth()->user()->can(SidulurPermissions::SETTING_VIEW), 403);

        $this->ticket_expiry_days = (int) Setting::get('ticket_expiry_days', config('sidulur.ticket_expiry_days', 30));
        $this->document_link_days = (int) Setting::get('document_link_days', config('sidulur.document_link_days', 30));
        $this->whatsapp_center = (string) Setting::get('whatsapp_center', config('sidulur.whatsapp.center', ''));
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can(SidulurPermissions::SETTING_UPDATE), 403);

        // x-mask hanya membatasi tampilan; server tetap menyimpan digit saja.
        $this->whatsapp_center = preg_replace('/\D+/', '', $this->whatsapp_center);

        $data = $this->validate([
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'ticket_expiry_days' => ['required', 'integer', 'min:1', 'max:365'],
            'document_link_days' => ['required', 'integer', 'min:1', 'max:365'],
            'whatsapp_center' => ['nullable', 'string', 'max:30'],
        ]);

        Setting::put('ticket_expiry_days', (string) $data['ticket_expiry_days']);
        Setting::put('document_link_days', (string) $data['document_link_days']);
        Setting::put('whatsapp_center', $data['whatsapp_center']);

        if (filled($data['password'])) {
            Setting::setPassword($data['password']);
        }

        $this->reset(['password', 'password_confirmation']);
        $this->dispatch('toast', message: 'Pengaturan disimpan.', type: 'success');
    }

    public function render()
    {
        return view('sidulur::livewire.pages.setting.index');
    }
}
