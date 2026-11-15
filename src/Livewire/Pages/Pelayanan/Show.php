<?php

namespace Bale\Sidulur\Livewire\Pages\Pelayanan;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Sidulur\Models\PelayananBidang;
use Bale\Sidulur\Services\DocumentService;
use Bale\Sidulur\Services\OfficerScope;
use Bale\Sidulur\Services\SubmissionFlow;
use Bale\Sidulur\SidulurPermissions;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('cms::layouts.app')]
#[Title('Detail Pelayanan Bidang')]
class Show extends Component
{
    use WithFileUploads;

    public PelayananBidang $pelayanan;

    public ?string $scheduled_at = null;

    public string $scheduled_note = '';

    public array $formData = [];

    public string $note = '';

    public $hasil;

    public function mount(PelayananBidang $pelayanan): void
    {
        TenantConnectionService::ensureActive();
        abort_unless(auth()->user()->can(SidulurPermissions::PELAYANAN_VIEW), 403);

        $this->assertBidang($pelayanan);

        $this->pelayanan = $pelayanan->load([
            'pendaftaran.citizen.familyCard',
            'pendaftaran.service',
            'pendaftaran.timelines',
            'bidang',
            'service',
        ]);

        $this->scheduled_at = $pelayanan->scheduled_at?->toDateString();
        $this->scheduled_note = (string) $pelayanan->scheduled_note;
        $this->formData = $pelayanan->form_data ?? [];
    }

    public function schedule(SubmissionFlow $flow): void
    {
        abort_unless(auth()->user()->can(SidulurPermissions::PELAYANAN_UPDATE), 403);

        $data = $this->validate([
            'scheduled_at' => ['required', 'date', 'after_or_equal:today'],
            'scheduled_note' => ['nullable', 'string', 'max:500'],
        ]);

        $flow->schedule($this->pelayanan, $data['scheduled_at'], $data['scheduled_note'] ?: null);
        $this->pelayanan->refresh();
        $this->dispatch('toast', message: 'Jadwal verifikasi lapangan disimpan.', type: 'success');
    }

    public function finish(SubmissionFlow $flow): void
    {
        abort_unless(auth()->user()->can(SidulurPermissions::PELAYANAN_UPDATE), 403);

        $this->validate(['formData' => ['array']]);

        $flow->finish($this->pelayanan, $this->formData, $this->note ?: null);
        $this->pelayanan->refresh();
        $this->dispatch('toast', message: 'Pengajuan diselesaikan.', type: 'success');
    }

    public function uploadHasil(DocumentService $documents): void
    {
        abort_unless(auth()->user()->can(SidulurPermissions::PELAYANAN_UPDATE), 403);

        $this->validate(['hasil' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096']]);

        $documents->upload($this->pelayanan->pendaftaran, $this->hasil, 'hasil');
        $this->reset('hasil');
        $this->dispatch('toast', message: 'Dokumen hasil diunggah.', type: 'success');
    }

    protected function assertBidang(PelayananBidang $pelayanan): void
    {
        $ids = OfficerScope::bidangIds();

        if ($ids !== null && ! in_array($pelayanan->bidang_id, $ids, true)) {
            abort(403);
        }
    }

    public function render()
    {
        return view('sidulur::livewire.pages.pelayanan.show');
    }
}
