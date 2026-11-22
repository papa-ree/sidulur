<?php

namespace Bale\Sidulur\Commands;

use Illuminate\Console\Command;

class PublishMigrationsSidulur extends Command
{
    protected $signature = 'sidulur:publish-migrations {--force : Timpa file migrasi yang sudah dipublikasikan}';

    protected $description = 'Publikasikan berkas migrasi bale/sidulur';

    public function handle(): int
    {
        $this->call('vendor:publish', [
            '--tag' => 'sidulur:migrations',
            '--force' => (bool) $this->option('force'),
        ]);

        $count = count(glob(database_path('migrations/sidulur/*.php')) ?: []);

        $this->info("{$count} berkas migrasi dipublikasikan ke database/migrations/sidulur.");

        return self::SUCCESS;
    }
}
