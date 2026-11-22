<?php

namespace Bale\Sidulur;

use Bale\Sidulur\Commands\BersihkanSyaratMaster;
use Bale\Sidulur\Commands\DemoSidulur;
use Bale\Sidulur\Commands\InstallSidulur;
use Bale\Sidulur\Commands\MigrateSidulur;
use Bale\Sidulur\Commands\PublishConfigSidulur;
use Bale\Sidulur\Commands\PublishMigrationsSidulur;
use Bale\Sidulur\Commands\SeedSidulur;
use Bale\Sidulur\Vada\SidulurContactContext;
use Bale\Vada\Services\ContextRegistry;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Component as LivewireComponent;
use Livewire\Livewire;
use Symfony\Component\Finder\Finder;

class SidulurServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sidulur.php', 'sidulur');

        $this->registerDocumentDisk();

        $this->commands([
            BersihkanSyaratMaster::class,
            DemoSidulur::class,
            InstallSidulur::class,
            MigrateSidulur::class,
            PublishConfigSidulur::class,
            PublishMigrationsSidulur::class,
            SeedSidulur::class,
        ]);
    }

    /**
     * Disk dokumen Sidulur belum tentu ada di config/filesystems aplikasi.
     * Tanpa ini unggah KTP/KK gagal dengan "Disk [sidulur] does not have a
     * configured driver".
     */
    protected function registerDocumentDisk(): void
    {
        $disk = config('sidulur.storage.disk', 'sidulur');

        if (config("filesystems.disks.{$disk}")) {
            return;
        }

        config(["filesystems.disks.{$disk}" => [
            'driver' => 'local',
            'root' => storage_path('app/'.$disk),
            'throw' => false,
        ]]);
    }

    public function boot(): void
    {
        $this->app->booted(function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'sidulur');
        $this->registerValidationMessages();
        $this->registerVadaContext();
        $this->offerPublishing();
        $this->registerLivewireComponents();
    }

    /**
     * Vada membaca konteks lewat kontraknya sendiri; kalau Vada tidak terpasang,
     * Sidulur tetap jalan tanpa panel chat.
     */
    protected function registerVadaContext(): void
    {
        if (! class_exists(ContextRegistry::class)) {
            return;
        }

        $this->app->make(ContextRegistry::class)
            ->register($this->app->make(SidulurContactContext::class));
    }

    /**
     * Aplikasi belum punya lang/id/validation.php, jadi error validasi tampil
     * sebagai kunci mentah. addLines() hanya menerima kunci datar, karena itu
     * file bersarang diratakan lebih dulu.
     */
    protected function registerValidationMessages(): void
    {
        $lines = require __DIR__.'/../resources/lang/id/validation.php';

        Lang::addLines(Arr::dot($lines), 'id');
    }

    protected function offerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/sidulur.php' => config_path('sidulur.php'),
        ], 'sidulur:config');

        $this->publishes($this->getMigrations(), 'sidulur:migrations');
    }

    /**
     * @return array<string, string>
     */
    protected function getMigrations(): array
    {
        $migrations = [];
        $sourcePath = __DIR__.'/../database/migrations/';

        foreach (glob($sourcePath.'*.php.stub') ?: [] as $file) {
            $migrations[$file] = database_path(
                'migrations/sidulur/'.str_replace('.php.stub', '.php', basename($file))
            );
        }

        return $migrations;
    }

    /**
     * Alias Livewire `sidulur.` mengikuti struktur folder kebab-case:
     * src/Livewire/Pages/Citizen/Index.php -> sidulur.pages.citizen.index
     */
    protected function registerLivewireComponents(): void
    {
        $basePath = __DIR__.'/Livewire';

        if (! is_dir($basePath)) {
            return;
        }

        $finder = new Finder;
        $finder->files()->in($basePath)->name('*.php');

        foreach ($finder as $file) {
            $relative = $file->getRelativePathname();
            $class = 'Bale\\Sidulur\\Livewire\\'.Str::beforeLast(
                str_replace(['/', '\\'], '\\', $relative),
                '.php'
            );

            if (! class_exists($class) || ! is_subclass_of($class, LivewireComponent::class)) {
                continue;
            }

            $segments = preg_split('#[\\/\\\\]#', Str::replaceLast('.php', '', $relative));
            $kebab = array_map(fn ($s) => Str::kebab($s), $segments);

            Livewire::component('sidulur.'.implode('.', $kebab), $class);
        }
    }
}
