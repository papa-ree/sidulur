<?php

namespace Bale\Sidulur\Livewire\Concerns;

use Bale\Sidulur\Models\Citizen;
use Bale\Sidulur\Models\Pendaftaran;
use Bale\Sidulur\Models\Service;
use Bale\Sidulur\Services\SubmissionFlow;
use Bale\Sidulur\SidulurPermissions;
use Bale\Sidulur\Support\FormSchema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Logika simpan pengajuan yang sama untuk halaman pendaftaran dan panel chat.
 *
 * Pemakai wajib menyediakan properti: citizenId, serviceId, formData,
 * requirementData, requirementFiles.
 */
trait RegistersSubmission
{
    /**
     * Persyaratan tambahan formulir (identitas & berkas warga bukan bagiannya).
     *
     * @return array<int, array<string, mixed>>
     */
    public function requirements(?Service $service = null): array
    {
        if (! $service && ! $this->serviceId) {
            return [];
        }

        $service ??= Service::query()->with('form')->findOrFail($this->serviceId);

        return FormSchema::normalize($service->form?->schema)['persyaratan'];
    }

    /**
     * Hapus satu berkas persyaratan dari state sebelum pengajuan disimpan.
     */
    public function removeRequirementFile(int $index, ?string $kode = null): void
    {
        $this->authorizeCreate();

        if ($kode !== null && isset($this->requirementFiles[$kode][$index])) {
            unset($this->requirementFiles[$kode][$index]);
            $this->requirementFiles[$kode] = array_values($this->requirementFiles[$kode]);
        }

        $this->resetValidation('requirementFiles.'.($kode ?? '*'));
    }

    /**
     * Sumber identitas yang belum lengkap di Data Warga.
     *
     * @return array<int, string>
     */
    public function identityMissing(): array
    {
        if (! $this->citizenId) {
            return [];
        }

        $citizen = $this->citizen();

        return FormSchema::identityMissing(
            $citizen->documents->where('kind', 'ktp')->isNotEmpty(),
            $citizen->familyCard?->documents->where('kind', 'kk')->isNotEmpty() ?? false,
            filled($citizen->phone),
        );
    }

    protected function authorizeCreate(): void
    {
        abort_unless(auth()->user()->can(SidulurPermissions::PENDAFTARAN_CREATE), 403);
    }

    /**
     * @param  array<string, UploadedFile>  $extraFiles  berkas dari sumber lain (mis. media chat)
     */
    protected function register(SubmissionFlow $flow, array $extraFiles = []): Pendaftaran
    {
        abort_unless($this->citizenId && $this->serviceId, 404);

        $missing = $this->identityMissing();

        // Identitas & berkas warga wajib lengkap; sumbernya Data Warga, bukan form ini.
        if ($missing !== []) {
            throw ValidationException::withMessages([
                'identitas' => __('Lengkapi :berkas di Data Warga sebelum menyimpan pendaftaran.', [
                    'berkas' => collect($missing)->map(fn (string $key) => FormSchema::identityLabel($key))->implode(', '),
                ]),
            ]);
        }

        $service = Service::query()->with('form')->findOrFail($this->serviceId);
        $schema = $service->form?->schema ?? [];
        $pending = $this->requirements($service);

        $files = $this->requirementFiles;

        foreach ($extraFiles as $kode => $file) {
            if ($file instanceof UploadedFile) {
                $files[$kode][] = $file;
            }
        }

        Validator::make(
            [
                'formData' => $this->formData,
                'requirementData' => $this->requirementData,
                'requirementFiles' => $files,
            ],
            array_merge([
                'formData' => ['array'],
                'requirementData' => ['array'],
                'requirementFiles' => ['array'],
            ],
                FormSchema::fieldRules($schema['fields'] ?? [], 'formData'),
                FormSchema::requirementValueRules($pending, 'requirementData'),
                FormSchema::requirementValueRules($pending, 'requirementFiles', files: true),
            ),
            [],
            array_merge(
                FormSchema::fieldLabels($schema['fields'] ?? [], 'formData'),
                FormSchema::requirementLabels($pending, 'requirementData'),
                FormSchema::requirementLabels($pending, 'requirementFiles'),
            )
        )->validate();

        $answers = array_filter($this->requirementData, fn ($value) => filled($value));

        if ($answers !== []) {
            $this->formData['_persyaratan'] = $answers;
        }

        return $flow->register(
            $this->citizen(),
            $service,
            $this->formData,
            array_filter($files),
        );
    }

    protected function citizen(): Citizen
    {
        return Citizen::query()->with(['familyCard.documents', 'documents'])->findOrFail($this->citizenId);
    }
}
