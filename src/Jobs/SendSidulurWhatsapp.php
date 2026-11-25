<?php

namespace Bale\Sidulur\Jobs;

use Bale\Wara\Facades\Wara;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Notifikasi WhatsApp keluar dari permintaan HTTP: gateway bisa lambat atau
 * kena cooldown, dan tiket tetap harus tersimpan lebih dulu.
 */
class SendSidulurWhatsapp implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public string $phone,
        public string $message,
        public ?string $deviceId = null,
    ) {
        $this->afterCommit = true;
    }

    public function handle(): void
    {
        Wara::sendMessage(
            $this->phone,
            $this->message,
            config('sidulur.whatsapp.purpose', 'sidulur'),
            deviceId: $this->deviceId,
        );
    }
}
