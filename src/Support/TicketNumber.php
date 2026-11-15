<?php

namespace Bale\Sidulur\Support;

use Bale\Sidulur\Models\Pendaftaran;
use Bale\Sidulur\Models\Setting;
use Illuminate\Support\Carbon;

class TicketNumber
{
    public const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public static function generate(): string
    {
        $prefix = 'SDLR-'.now()->format('Ymd').'-';

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $suffix = '';

            for ($i = 0; $i < 4; $i++) {
                $suffix .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }

            $ticket = $prefix.$suffix;

            if (! Pendaftaran::withTrashed()->where('ticket_number', $ticket)->exists()) {
                return $ticket;
            }
        }

        throw new \RuntimeException('Gagal membuat nomor tiket unik.');
    }

    public static function expiry(): Carbon
    {
        $days = (int) (Setting::get('ticket_expiry_days')
            ?: config('sidulur.ticket_expiry_days', 30));

        return now()->addDays(max(1, $days));
    }
}
