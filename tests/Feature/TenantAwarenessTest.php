<?php

use Bale\Cms\Middleware\SwitchBaleConnection;
use Bale\Cms\Services\TenantManager;
use Bale\Cms\Traits\UsesTenantConnection;
use Bale\Sidulur\Models\Bidang;
use Bale\Sidulur\Models\Officer;
use Bale\Sidulur\Models\Setting;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

require_once __DIR__.'/../helpers.php';

uses()->beforeEach(sidulurTenant());

/**
 * Buat koneksi tenant kedua (kosong) lalu jadikan aktif.
 */
function sidulurUseSecondTenant(): void
{
    $connection = 'sidulur_test_b';

    config()->set("database.connections.{$connection}", [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]);

    DB::purge($connection);

    $original = DB::getDefaultConnection();
    DB::setDefaultConnection($connection);
    config()->set('database.default', $connection);

    foreach (glob(database_path('migrations/sidulur/*.php')) as $file) {
        (require $file)->up();
    }

    DB::setDefaultConnection($original);
    config()->set('database.default', $original);

    $property = new ReflectionProperty(TenantManager::class, 'activeConnection');
    $property->setAccessible(true);
    $property->setValue(null, $connection);
}

it('menempelkan seluruh model pada koneksi tenant aktif', function () {
    foreach (glob(__DIR__.'/../../src/Models/*.php') as $file) {
        $class = 'Bale\\Sidulur\\Models\\'.basename($file, '.php');

        expect(class_uses_recursive($class))->toHaveKey(UsesTenantConnection::class)
            ->and((new $class)->getConnectionName())->toBe('sidulur_test');
    }
});

it('menyimpan petugas dengan user landlord tanpa foreign key tenant', function () {
    $officer = Officer::query()->create(['user_id' => (string) Str::uuid()]);

    expect($officer->exists)->toBeTrue()
        ->and(Officer::query()->count())->toBe(1);
});

it('memisahkan data dan cache setting antar tenant', function () {
    sidulurBidang('Bidang A');
    Setting::put('ticket_expiry_days', '7');

    sidulurUseSecondTenant();

    expect(Bidang::query()->count())->toBe(0)
        ->and(Setting::get('ticket_expiry_days', '30'))->toBe('30');

    Setting::put('ticket_expiry_days', '10');

    $property = new ReflectionProperty(TenantManager::class, 'activeConnection');
    $property->setAccessible(true);
    $property->setValue(null, 'sidulur_test');

    expect(Setting::get('ticket_expiry_days'))->toBe('7')
        ->and(Bidang::query()->where('nama', 'Bidang A')->exists())->toBeTrue();
});

it('mengaktifkan koneksi tenant sebelum route model binding', function () {
    $router = app('router');

    foreach (['sidulur.pendaftaran.show', 'sidulur.citizen.show', 'sidulur.pelayanan.show', 'sidulur.verlap.show', 'sidulur.form.edit'] as $name) {
        $middleware = array_values(array_filter(
            $router->gatherRouteMiddleware($router->getRoutes()->getByName($name)),
            fn ($middleware) => is_string($middleware),
        ));

        $switch = array_search(SwitchBaleConnection::class, $middleware, true);
        $bindings = array_search(SubstituteBindings::class, $middleware, true);

        expect($switch)->not->toBeFalse()
            ->and($bindings)->not->toBeFalse()
            ->and($switch)->toBeLessThan($bindings);
    }
});

it('tidak memakai aturan exists tanpa koneksi tenant', function () {
    $offenders = collect(File::allFiles(__DIR__.'/../../src'))
        ->filter(fn ($file) => str_contains($file->getContents(), 'exists:sidulur_'))
        ->map(fn ($file) => $file->getRelativePathname())
        ->values()
        ->all();

    expect($offenders)->toBe([]);
});
