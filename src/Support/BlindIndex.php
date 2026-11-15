<?php

namespace Bale\Sidulur\Support;

class BlindIndex
{
    /**
     * Hash deterministik untuk pencarian exact pada kolom terenkripsi.
     * Kunci HARUS sama dengan SIDULUR_INDEX_KEY di bale-front.
     */
    public static function make(?string $value): ?string
    {
        $normalized = static::normalize($value);

        if ($normalized === null) {
            return null;
        }

        return hash_hmac('sha256', $normalized, (string) config('sidulur.index_key'));
    }

    /**
     * NIK/KK/HP disimpan sebagai digit saja; nomor HP diawali 62.
     */
    public static function normalize(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        return $digits;
    }
}
