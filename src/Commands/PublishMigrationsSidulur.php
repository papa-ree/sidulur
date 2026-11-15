<?php

namespace Bale\Sidulur\Commands;

use Illuminate\Console\Command;

class PublishMigrationsSidulur extends Command
{
    protected $signature = 'sidulur:publish-migrations {--force : Timpa file migrasi yang sudah dipublikasikan}';

    protected $description = 'Publish migration files for bale/sidulur';

    public function handle(): int
    {
        $this->call('vendor:publish', [
            '--tag' => 'sidulur:migrations',
            '--force' => (bool) $this->option('force'),
        ]);

        $count = count(glob(database_path('migrations/sidulur/*.php')) ?: []);

        $this->info("Published {$count} migration file(s) to database/migrations/sidulur.");

        return self::SUCCESS;
    }
}
