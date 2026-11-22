<?php

namespace Bale\Sidulur\Services;

use Bale\Cms\Services\TenantManager;
use Bale\Sidulur\Models\Citizen;
use Bale\Sidulur\Models\PelayananBidang;
use Bale\Sidulur\Models\Pendaftaran;
use Bale\Sidulur\Models\Service;
use Bale\Sidulur\Models\Timeline;
use Bale\Sidulur\Support\TicketNumber;
use Illuminate\Http\UploadedFile;

class SubmissionFlow
{
    public function __construct(protected Notifier $notifier, protected DocumentService $documents) {}

    /**
     * @param  array<string, mixed>  $formData
     * @param  array<string, array<int, UploadedFile>>  $requirementFiles  berkas per kode persyaratan
     */
    public function register(Citizen $citizen, Service $service, array $formData, array $requirementFiles = []): Pendaftaran
    {
        return TenantManager::connection()->transaction(function () use ($citizen, $service, $formData, $requirementFiles) {
            $schema = $service->form?->schema ?? ['persyaratan' => [], 'fields' => [], 'verifikasi' => []];

            $pendaftaran = Pendaftaran::create([
                'ticket_number' => TicketNumber::generate(),
                'citizen_id' => $citizen->getKey(),
                'service_id' => $service->getKey(),
                'form_snapshot' => $schema,
                'form_data' => $formData,
                'status' => Pendaftaran::STATUS_BARU,
                'created_by' => auth()->user()?->uuid,
                'expires_at' => TicketNumber::expiry(),
            ]);

            foreach ($requirementFiles as $kode => $uploads) {
                foreach ((array) $uploads as $file) {
                    if ($file instanceof UploadedFile) {
                        $this->documents->upload($pendaftaran, $file, $kode);
                    }
                }
            }

            $this->timeline($pendaftaran, 'pendaftaran', null, Pendaftaran::STATUS_BARU, 'Tiket dibuat.');

            $this->notifier->ticketCreated($citizen, $pendaftaran);

            return $pendaftaran;
        });
    }

    public function finishAtDesk(Pendaftaran $pendaftaran, ?string $note = null): void
    {
        TenantManager::connection()->transaction(function () use ($pendaftaran, $note) {
            $pendaftaran->update([
                'status' => Pendaftaran::STATUS_SELESAI,
                'completed_at' => now(),
            ]);

            $this->timeline(
                $pendaftaran,
                'pendaftaran',
                Pendaftaran::STATUS_BARU,
                Pendaftaran::STATUS_SELESAI,
                $note ?: 'Diselesaikan di meja pendaftaran.'
            );

            $this->notifier->completed($pendaftaran->citizen, $pendaftaran);
        });
    }

    public function forward(Pendaftaran $pendaftaran, ?string $note = null): PelayananBidang
    {
        return TenantManager::connection()->transaction(function () use ($pendaftaran, $note) {
            if ($pendaftaran->pelayananBidang()->exists()) {
                throw new \RuntimeException('Pendaftaran ini sudah diteruskan ke bidang.');
            }

            $service = $pendaftaran->service;

            $pelayanan = PelayananBidang::create([
                'pendaftaran_id' => $pendaftaran->getKey(),
                'bidang_id' => $service->bidang_id,
                'service_id' => $service->getKey(),
                'status' => PelayananBidang::STATUS_BARU,
                'form_snapshot' => $pendaftaran->form_snapshot,
            ]);

            $pendaftaran->update([
                'status' => Pendaftaran::STATUS_SELESAI,
                'forwarded_at' => now(),
                'completed_at' => now(),
            ]);

            $this->timeline(
                $pendaftaran,
                'pendaftaran',
                Pendaftaran::STATUS_BARU,
                Pendaftaran::STATUS_SELESAI,
                $note ?: 'Diteruskan ke '.$service->bidang?->nama.'.',
                $pelayanan
            );

            $this->timeline($pendaftaran, 'bidang', null, PelayananBidang::STATUS_BARU, 'Masuk antrean bidang.', $pelayanan);

            return $pelayanan;
        });
    }

    public function schedule(PelayananBidang $pelayanan, string $date, ?string $note = null): void
    {
        TenantManager::connection()->transaction(function () use ($pelayanan, $date, $note) {
            if ($pelayanan->status !== PelayananBidang::STATUS_BARU) {
                throw new \RuntimeException('Hanya pengajuan berstatus baru yang dapat dijadwalkan.');
            }

            $pelayanan->update([
                'status' => PelayananBidang::STATUS_DIJADWALKAN,
                'scheduled_at' => $date,
                'scheduled_note' => $note,
            ]);

            $this->timeline(
                $pelayanan->pendaftaran,
                'bidang',
                PelayananBidang::STATUS_BARU,
                PelayananBidang::STATUS_DIJADWALKAN,
                $note ?: 'Verifikasi lapangan dijadwalkan.',
                $pelayanan
            );

            $this->notifier->verlapScheduled($pelayanan->pendaftaran->citizen, $pelayanan);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function recordVerlap(PelayananBidang $pelayanan, array $data, ?string $note = null): void
    {
        TenantManager::connection()->transaction(function () use ($pelayanan, $data, $note) {
            if ($pelayanan->status !== PelayananBidang::STATUS_DIJADWALKAN) {
                throw new \RuntimeException('Pengajuan belum dijadwalkan untuk verifikasi lapangan.');
            }

            $pelayanan->update([
                'status' => PelayananBidang::STATUS_VERLAP_SELESAI,
                'verlap_snapshot' => $pelayanan->form_snapshot['verifikasi'] ?? [],
                'verlap_data' => $data,
                'verlap_at' => now(),
            ]);

            $this->timeline(
                $pelayanan->pendaftaran,
                'verlap',
                PelayananBidang::STATUS_DIJADWALKAN,
                PelayananBidang::STATUS_VERLAP_SELESAI,
                $note ?: 'Hasil verifikasi lapangan disimpan.',
                $pelayanan
            );
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function finish(PelayananBidang $pelayanan, array $data, ?string $note = null): void
    {
        TenantManager::connection()->transaction(function () use ($pelayanan, $data, $note) {
            if (! $pelayanan->bolehRespon()) {
                throw new \RuntimeException('Status pengajuan tidak dapat diselesaikan.');
            }

            $from = $pelayanan->status;

            $pelayanan->update([
                'status' => PelayananBidang::STATUS_SELESAI,
                'form_data' => $data,
                'handled_by' => auth()->user()?->uuid,
                'completed_at' => now(),
            ]);

            $this->timeline(
                $pelayanan->pendaftaran,
                'bidang',
                $from,
                PelayananBidang::STATUS_SELESAI,
                $note ?: 'Pengajuan diselesaikan.',
                $pelayanan
            );

            $this->notifier->completed($pelayanan->pendaftaran->citizen, $pelayanan->pendaftaran);
        });
    }

    protected function timeline(
        Pendaftaran $pendaftaran,
        string $stage,
        ?string $from,
        string $to,
        ?string $note = null,
        ?PelayananBidang $pelayanan = null
    ): Timeline {
        return Timeline::create([
            'pendaftaran_id' => $pendaftaran->getKey(),
            'pelayanan_bidang_id' => $pelayanan?->getKey(),
            'stage' => $stage,
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
            'actor_id' => auth()->user()?->uuid,
        ]);
    }
}
