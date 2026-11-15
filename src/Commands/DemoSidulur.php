<?php

namespace Bale\Sidulur\Commands;

use Bale\Cms\Models\BaleList;
use Bale\Cms\Services\TenantManager;
use Bale\Sidulur\Models\Citizen;
use Bale\Sidulur\Services\DemoSeeder;
use Bale\Sidulur\Services\Notifier;
use Illuminate\Console\Command;
use Throwable;

class DemoSidulur extends Command
{
    protected $signature = 'sidulur:demo {--tenant= : Slug tenant yang akan diisi data demo}';

    protected $description = 'Isi data demo Sidulur: kartu keluarga, warga, pengajuan, antrean bidang, verlap, dan dashboard (idempotent)';

    public function handle(): int
    {
        $tenantSlug = $this->option('tenant');

        if (! $tenantSlug) {
            $tenants = BaleList::all();

            if ($tenants->isEmpty()) {
                $this->error('No tenants found in bale_lists table.');

                return self::FAILURE;
            }

            $tenantSlug = $this->choice(
                'Which tenant database do you want to fill with demo data?',
                $tenants->pluck('slug')->toArray()
            );
        }

        try {
            $tenant = BaleList::where('slug', $tenantSlug)->firstOrFail();

            $this->info("Initializing connection for tenant: {$tenant->slug}");
            TenantManager::initializeFromBaleUuid($tenant->id);

            if (! TenantManager::getActiveConnection()) {
                throw new \Exception("Failed to activate connection for tenant {$tenant->slug}");
            }

            // Data demo tidak boleh mengirim WhatsApp ke nomor contoh.
            app()->bind(Notifier::class, fn () => new class extends Notifier
            {
                protected function send(Citizen $citizen, string $message): void {}
            });

            $this->info("Seeding demo data (DB: {$tenant->database_name})...");

            $summary = app(DemoSeeder::class)->run();

            $this->info(sprintf(
                'Demo siap: %d kartu keluarga, %d warga, %d dokumen baru, %d pengajuan baru.',
                $summary['kartu_keluarga'],
                $summary['warga'],
                $summary['dokumen'],
                $summary['pendaftaran']
            ));

            if ($summary['password']) {
                $this->line("Password dashboard eksekutif: {$summary['password']}");
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Demo seeding failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
