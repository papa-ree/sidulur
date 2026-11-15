<?php

namespace Bale\Sidulur\Commands;

use Bale\Cms\Models\BaleList;
use Bale\Cms\Services\TenantManager;
use Illuminate\Console\Command;
use Throwable;

class MigrateSidulur extends Command
{
    protected $signature = 'sidulur:migrate {--tenant= : Slug tenant yang akan dimigrasi}';

    protected $description = 'Run Sidulur migrations for a specific tenant database';

    public function handle(): int
    {
        $path = 'database/migrations/sidulur';
        $published = glob(base_path($path.'/*.php')) ?: [];

        if ($published === []) {
            $this->error('Migrasi belum dipublikasikan. Jalankan dulu: php artisan sidulur:publish-migrations');

            return self::FAILURE;
        }

        $tenantSlug = $this->option('tenant');

        if (! $tenantSlug) {
            $tenants = BaleList::all();

            if ($tenants->isEmpty()) {
                $this->error('No tenants found in bale_lists table.');

                return self::FAILURE;
            }

            $tenantSlug = $this->choice(
                'Which tenant database do you want to migrate?',
                $tenants->pluck('slug')->toArray()
            );
        }

        try {
            $tenant = BaleList::where('slug', $tenantSlug)->firstOrFail();

            TenantManager::initializeFromBaleUuid($tenant->id);
            $connection = TenantManager::getActiveConnection();

            if (! $connection) {
                throw new \Exception("Failed to activate connection for tenant {$tenant->slug}");
            }

            $this->info("Migrating Sidulur tables (DB: {$tenant->database_name})...");

            $this->call('migrate', [
                '--database' => $connection,
                '--path' => $path,
                '--force' => true,
            ]);

            $this->info("Migration for tenant {$tenant->slug} completed.");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Migration failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
