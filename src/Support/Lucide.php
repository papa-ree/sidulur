<?php

namespace Bale\Sidulur\Support;

/**
 * Ikon Lucide dari nama teks bebas (kolom `icon` bidang/layanan).
 *
 * `x-lucide-*` hanya untuk nama statis, sedangkan kolom `icon` bisa berisi
 * nama yang tidak ada; `svg()` melempar exception untuk nama tak dikenal.
 */
class Lucide
{
    public static function render(?string $name, string $class = 'size-5'): string
    {
        try {
            return svg('lucide-'.trim((string) $name), $class)->toHtml();
        } catch (\Throwable) {
            return svg('lucide-file-text', $class)->toHtml();
        }
    }
}
