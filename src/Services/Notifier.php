<?php

namespace Bale\Sidulur\Services;

use Bale\Sidulur\Jobs\SendSidulurWhatsapp;
use Bale\Sidulur\Models\Citizen;
use Bale\Sidulur\Models\PelayananBidang;
use Bale\Sidulur\Models\Pendaftaran;

class Notifier
{
    public function ticketCreated(Citizen $citizen, Pendaftaran $pendaftaran): void
    {
        $this->send($citizen, sprintf(
            'Sidulur: tiket %s untuk %s (layanan %s) telah dibuat. Simpan nomor tiket untuk pengecekan status.',
            $pendaftaran->ticket_number,
            $citizen->nama,
            $pendaftaran->service?->nama ?? 'layanan'
        ));
    }

    public function verlapScheduled(Citizen $citizen, PelayananBidang $pelayanan): void
    {
        $this->send($citizen, sprintf(
            'Sidulur: verifikasi lapangan tiket %s (%s) dijadwalkan pada %s.',
            $pelayanan->pendaftaran?->ticket_number,
            $citizen->nama,
            $pelayanan->scheduled_at?->translatedFormat('d F Y') ?? '-'
        ));
    }

    public function completed(Citizen $citizen, Pendaftaran $pendaftaran): void
    {
        $this->send($citizen, sprintf(
            'Sidulur: pengajuan tiket %s (%s) telah selesai. Terima kasih.',
            $pendaftaran->ticket_number,
            $citizen->nama
        ));
    }

    protected function send(Citizen $citizen, string $message): void
    {
        $phone = $citizen->phone;

        if (blank($phone)) {
            return;
        }

        SendSidulurWhatsapp::dispatch($phone, $message);
    }
}
