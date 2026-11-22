<?php

namespace Bale\Sidulur\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishConfigSidulur extends Command
{
    protected $signature = 'sidulur:publish-config {--force : Timpa berkas konfigurasi yang sudah ada}';

    protected $description = 'Publikasikan berkas konfigurasi bale/sidulur';

    public function handle(): int
    {
        $source = __DIR__.'/../../config/sidulur.php';
        $target = config_path('sidulur.php');

        if (! File::exists($source)) {
            $this->error("Sumber konfigurasi tidak ditemukan: {$source}");

            return self::FAILURE;
        }

        if (File::exists($target) && ! $this->option('force')) {
            $this->line('Berkas konfigurasi sudah ada: <comment>config/sidulur.php</comment>. Gunakan <info>--force</info> untuk menimpa.');

            return self::SUCCESS;
        }

        File::copy($source, $target);
        $this->info('Berkas konfigurasi dipublikasikan: <info>config/sidulur.php</info>');

        return self::SUCCESS;
    }
}
