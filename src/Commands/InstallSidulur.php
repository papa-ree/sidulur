<?php

namespace Bale\Sidulur\Commands;

use Bale\Sidulur\SidulurPermissions;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

class InstallSidulur extends Command
{
    protected $signature = 'sidulur:install {--tenant= : Slug tenant untuk langkah migration} {--all : Jalankan semua langkah tanpa prompt}';

    protected $description = 'Installer bale/sidulur (config, permission, migration)';

    public function handle(): int
    {
        $option = $this->option('all') ? 0 : $this->choice(
            'What would you like to install/run?',
            [
                0 => 'All (Config, Migrations & Permissions)',
                1 => 'Publish Config Only',
                2 => 'Role & Permissions Only',
                3 => 'Migrations Only',
            ],
            0
        );

        $all = in_array($option, ['All (Config, Migrations & Permissions)', 0], true);

        try {
            if ($all || in_array($option, ['Publish Config Only', 1], true)) {
                $this->call('sidulur:publish-config', ['--force' => true]);
            }

            if ($all || in_array($option, ['Role & Permissions Only', 2], true)) {
                $this->seedPermissions();
            }

            if ($all || in_array($option, ['Migrations Only', 3], true)) {
                if ($this->call('sidulur:publish-migrations', ['--force' => true]) !== self::SUCCESS) {
                    return self::FAILURE;
                }

                if ($this->call('sidulur:migrate', ['--tenant' => $this->option('tenant')]) !== self::SUCCESS) {
                    return self::FAILURE;
                }
            }

            $this->info('  Installation step(s) completed successfully.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('  [ERROR] Installation failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    protected function seedPermissions(): void
    {
        foreach (SidulurPermissions::ALL as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission],
                ['guard_name' => 'web']
            );
        }

        $rootRole = Role::where('name', 'root')->first();

        if ($rootRole) {
            $rootRole->givePermissionTo(SidulurPermissions::ALL);
        }

        $adminRole = Role::firstOrCreate(
            ['name' => 'bale-sidulur-admin'],
            ['guard_name' => 'web'],
        );
        $adminRole->syncPermissions(SidulurPermissions::ALL);

        $roles = [
            'bale-sidulur-pendaftaran' => [
                SidulurPermissions::VIEW,
                SidulurPermissions::CITIZEN_VIEW,
                SidulurPermissions::CITIZEN_CREATE,
                SidulurPermissions::CITIZEN_UPDATE,
                SidulurPermissions::SERVICE_VIEW,
                SidulurPermissions::PENDAFTARAN_VIEW,
                SidulurPermissions::PENDAFTARAN_CREATE,
            ],
            'bale-sidulur-bidang' => [
                SidulurPermissions::VIEW,
                SidulurPermissions::CITIZEN_VIEW,
                SidulurPermissions::PELAYANAN_VIEW,
                SidulurPermissions::PELAYANAN_UPDATE,
            ],
            'bale-sidulur-lapangan' => [
                SidulurPermissions::VIEW,
                SidulurPermissions::VERLAP_VIEW,
                SidulurPermissions::VERLAP_UPDATE,
            ],
        ];

        foreach ($roles as $name => $permissions) {
            Role::firstOrCreate(['name' => $name], ['guard_name' => 'web'])
                ->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info('Permissions seeded.');
    }
}
