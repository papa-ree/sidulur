<?php

namespace Bale\Sidulur\Commands;

use Bale\Cms\Models\BaleList;
use Bale\Cms\Services\TenantManager;
use Bale\Sidulur\Models\Form;
use Bale\Sidulur\Support\FormSchema;
use Illuminate\Console\Command;
use Throwable;

class BersihkanSyaratMaster extends Command
{
    protected $signature = 'sidulur:bersihkan-syarat-master {--tenant= : Slug tenant yang dibersihkan}';

    protected $description = 'Hapus syarat identitas (KTP/KK/HP) warisan Form Builder karena kini otomatis';

    public function handle(): int
    {
        $tenantSlug = $this->option('tenant');

        if (! $tenantSlug) {
            $tenants = BaleList::all();

            if ($tenants->isEmpty()) {
                $this->error('Tidak ada tenant pada tabel bale_lists.');

                return self::FAILURE;
            }

            $tenantSlug = $this->choice(
                'Tenant mana yang dibersihkan?',
                $tenants->pluck('slug')->toArray()
            );
        }

        try {
            $tenant = BaleList::where('slug', $tenantSlug)->firstOrFail();
            TenantManager::initializeFromBaleUuid($tenant->id);

            $dihapus = 0;

            foreach (Form::query()->get() as $form) {
                $lama = $form->schema['persyaratan'] ?? [];
                $schema = FormSchema::normalize($form->schema);

                if (count($schema['persyaratan']) === count($lama)) {
                    continue;
                }

                $dihapus += count($lama) - count($schema['persyaratan']);

                $form->update([
                    'schema' => $schema,
                    'version' => $form->version + 1,
                ]);
            }

            $this->info("Selesai. {$dihapus} syarat identitas dihapus dari tenant {$tenant->slug}.");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Pembersihan gagal: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
