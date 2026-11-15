<?php

namespace Bale\Sidulur\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishConfigSidulur extends Command
{
    protected $signature = 'sidulur:publish-config {--force : Overwrite existing configuration file}';

    protected $description = 'Publish configuration file for bale/sidulur';

    public function handle(): int
    {
        $source = __DIR__.'/../../config/sidulur.php';
        $target = config_path('sidulur.php');

        if (! File::exists($source)) {
            $this->error("Config source not found: {$source}");

            return self::FAILURE;
        }

        if (File::exists($target) && ! $this->option('force')) {
            $this->line('Config file already exists: <comment>config/sidulur.php</comment>. Use <info>--force</info> to overwrite.');

            return self::SUCCESS;
        }

        File::copy($source, $target);
        $this->info('Published config file: <info>config/sidulur.php</info>');

        return self::SUCCESS;
    }
}
