<?php

namespace Bale\Sidulur\Livewire\Pages\Pelayanan;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Sidulur\Models\PelayananBidang;
use Bale\Sidulur\Services\DocumentService;
use Bale\Sidulur\Services\OfficerScope;
use Bale\Sidulur\Services\SubmissionFlow;
use Bale\Sidulur\SidulurPermissions;
use Bale\Sidulur\Support\FormSchema;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('cms::layouts.app')]
#[Title('Detail Pengajuan')]
class Show extends Component
{
    use WithFileUploads;

    public PelayananBidang $pelayanan;

    public ?string $scheduled_at = null;

    public string $scheduled_note = '';

    public array $formData = [];

    public array $requirements = [];

    public string $note = '';

    public $hasil;

    public string $hasil_note = '';

    public function mount(PelayananBidang $pelayanan): void
    {
        TenantConnectionService::ensureActive();
        abort_unless(auth()->user()->can(SidulurPermissions::PELAYANAN_VIEW), 403);

        $this->assertBidang($pelayanan);

        $this->pelayanan = $pelayanan->load([
            'pendaftaran.citizen.familyCard.documents',
            'pendaftaran.citizen.documents',
            'pendaftaran.documents',
            'pendaftaran.service',
            'pendaftaran.timelines',
            'bidang',
            'service',
        ]);

        $this->scheduled_at = $pelayanan->scheduled_at?->toDateString();
        $this->scheduled_note = (string) $pelayanan->scheduled_note;
        $this->formData = $pelayanan->form_data ?? [];
        $this->requirements = $this->formData['_diperiksa'] ?? [];
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

    public function finish(SubmissionFlow $flow, DocumentService $documents): void
    {
        abort_unless(auth()->user()->can(SidulurPermissions::PELAYANAN_UPDATE), 403);

        $missing = $this->identityMissing();

        // Identitas & berkas warga bersumber Data Warga, bukan form ini.
        if ($missing !== []) {
            throw ValidationException::withMessages([
                'identitas' => __('Lengkapi :berkas di Data Warga sebelum menyelesaikan pengajuan.', [
                    'berkas' => collect($missing)->map(fn (string $key) => FormSchema::identityLabel($key))->implode(', '),
                ]),
            ]);
        }

        $requirements = $this->requirementContext();
        $fields = $this->pelayanan->form_snapshot['fields'] ?? [];
        $fileRules = [];

        // Field berkas butuh aturan tipe/ukuran; FormSchema hanya tahu required.
        foreach ($fields as $field) {
            if (($field['type'] ?? 'text') === 'file' && filled($field['key'] ?? null)) {
                $fileRules['formData.'.$field['key']] = [
                    ($field['required'] ?? false) ? 'required' : 'nullable',
                    'file',
                    'mimes:jpg,jpeg,png,webp,pdf',
                    'max:4096',
                ];
            }
        }

        $this->validate(
            array_merge(
                ['formData' => ['array']],
                FormSchema::fieldRules($fields, 'formData'),
                $fileRules,
                FormSchema::requirementRules($requirements),
                FormSchema::identityRule(),
            ),
            [],
            array_merge(
                FormSchema::fieldLabels($fields, 'formData'),
                FormSchema::requirementLabels($requirements),
                ['requirements.'.FormSchema::IDENTITY_KEY => FormSchema::identityTitle()],
            )
        );

        foreach ($fields as $field) {
            $key = $field['key'] ?? null;

            if (($field['type'] ?? 'text') !== 'file' || ! ($this->formData[$key] ?? null) instanceof UploadedFile) {
                continue;
            }

            $this->formData[$key] = $documents
                ->upload($this->pelayanan->pendaftaran, $this->formData[$key], 'respon')
                ->original_name;
        }

        $this->formData['_diperiksa'] = array_filter($this->requirements);

        $flow->finish($this->pelayanan, $this->formData, $this->note ?: null);
        $this->pelayanan->refresh();
        $this->dispatch('toast', message: 'Pengajuan diselesaikan.', type: 'success');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function requirementContext(): array
    {
        return FormSchema::normalize($this->pelayanan->form_snapshot)['persyaratan'];
    }

    /**
     * Sumber identitas yang belum lengkap di Data Warga.
     *
     * @return array<int, string>
     */
    protected function identityMissing(): array
    {
        $citizen = $this->pelayanan->pendaftaran->citizen;

        return FormSchema::identityMissing(
            $citizen->documents->where('kind', 'ktp')->isNotEmpty(),
            $citizen->familyCard?->documents->where('kind', 'kk')->isNotEmpty() ?? false,
            filled($citizen->phone),
        );
    }

    public function uploadHasil(DocumentService $documents): void
    {
        abort_unless(auth()->user()->can(SidulurPermissions::PELAYANAN_UPDATE), 403);
        abort_unless($this->pelayanan->bolehUnggahHasil(), 403);

        $pendaftaran = $this->pelayanan->pendaftaran;
        $replacing = $pendaftaran->documents()->where('kind', 'hasil')->exists();

        $this->validate([
            'hasil' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'hasil_note' => [$replacing ? 'required' : 'nullable', 'string', 'max:500'],
        ]);

        $document = $documents->replace($pendaftaran, $this->hasil, 'hasil');

        if ($replacing) {
            $form = $this->pelayanan->form_data ?? [];
            $form['_hasil_notes'][] = [
                'at' => now()->toDateTimeString(),
                'note' => $this->hasil_note,
                'document_id' => $document->getKey(),
            ];

            $this->pelayanan->update(['form_data' => $form]);
            // finish() menimpa form_data dengan state komponen; samakan agar catatan tidak hilang.
            $this->formData = $this->pelayanan->form_data ?? [];
        }

        $this->reset('hasil', 'hasil_note');
        $this->pelayanan->refresh();
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
        // Jangan pakai nama `requirements`: properti publik $requirements
        // (checklist) menimpa data view dengan nama yang sama.
        return view('sidulur::livewire.pages.pelayanan.show', [
            'syarat' => $this->requirementContext(),
            'identityMissing' => $this->identityMissing(),
            'requirementValues' => $this->pelayanan->pendaftaran->form_data['_persyaratan'] ?? [],
            'requirementDocuments' => $this->pelayanan->pendaftaran->documents,
        ]);
    }
}
