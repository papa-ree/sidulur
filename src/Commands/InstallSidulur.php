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
            'Apa yang ingin dijalankan?',
            [
                0 => 'Semua (Config, Migrasi & Permission)',
                1 => 'Publikasikan config saja',
                2 => 'Role & permission saja',
                3 => 'Migrasi saja',
            ],
            0
        );

        $all = in_array($option, ['Semua (Config, Migrasi & Permission)', 0], true);

        try {
            if ($all || in_array($option, ['Publikasikan config saja', 1], true)) {
                $this->call('sidulur:publish-config', ['--force' => true]);
            }

            if ($all || in_array($option, ['Role & permission saja', 2], true)) {
                $this->seedPermissions();
            }

            if ($all || in_array($option, ['Migrasi saja', 3], true)) {
                if ($this->call('sidulur:publish-migrations', ['--force' => true]) !== self::SUCCESS) {
                    return self::FAILURE;
                }

                if ($this->call('sidulur:migrate', ['--tenant' => $this->option('tenant')]) !== self::SUCCESS) {
                    return self::FAILURE;
                }
            }

            $this->info('  Langkah instalasi selesai.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('  [ERROR] Instalasi gagal: '.$e->getMessage());

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
            'bale-sidulur-pendaftaran' => SidulurPermissions::FLAGS['is_pendaftaran'],
            'bale-sidulur-bidang' => SidulurPermissions::FLAGS['is_bidang'],
            'bale-sidulur-lapangan' => SidulurPermissions::FLAGS['is_lapangan'],
        ];

        foreach ($roles as $name => $permissions) {
            Role::firstOrCreate(['name' => $name], ['guard_name' => 'web'])
                ->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info('Permission berhasil dibuat.');
    }
}
