<?php

namespace Bale\Sidulur\Services;

use Bale\Sidulur\Models\Citizen;
use Bale\Sidulur\Models\PelayananBidang;
use Bale\Sidulur\Models\Pendaftaran;
use Bale\Wara\Facades\Wara;
use Throwable;

class Notifier
{
    public function ticketCreated(Citizen $citizen, Pendaftaran $pendaftaran): void
    {
        $this->send($citizen, sprintf(
            'Sidulur: tiket %s untuk layanan %s telah dibuat. Simpan nomor tiket untuk pengecekan status.',
            $pendaftaran->ticket_number,
            $pendaftaran->service?->nama ?? 'layanan'
        ));
    }

    public function verlapScheduled(Citizen $citizen, PelayananBidang $pelayanan): void
    {
        $this->send($citizen, sprintf(
            'Sidulur: verifikasi lapangan untuk tiket %s dijadwalkan pada %s.',
            $pelayanan->pendaftaran?->ticket_number,
            $pelayanan->scheduled_at?->translatedFormat('d F Y') ?? '-'
        ));
    }

    public function completed(Citizen $citizen, Pendaftaran $pendaftaran): void
    {
        $this->send($citizen, sprintf(
            'Sidulur: pengajuan dengan tiket %s telah selesai. Terima kasih.',
            $pendaftaran->ticket_number
        ));
    }

    protected function send(Citizen $citizen, string $message): void
    {
        $phone = $citizen->phone;

        if (blank($phone)) {
            return;
        }

        try {
            Wara::sendMessage($phone, $message, config('sidulur.whatsapp.purpose', 'sidulur'));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
