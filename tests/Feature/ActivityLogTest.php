<?php

use Bale\Cms\Models\BaleUser;
use Bale\Cms\Models\User;
use Bale\Cms\Services\TenantManager;
use Bale\Core\Models\ActivityLog;
use Bale\Core\Traits\LogsActivity;
use Bale\Sidulur\Http\Controllers\DocumentController;
use Bale\Sidulur\Livewire\Pages\Citizen\Show as CitizenShow;
use Bale\Sidulur\Livewire\Pages\Officer\Form as OfficerForm;
use Bale\Sidulur\Models\Citizen;
use Bale\Sidulur\Models\Document;
use Bale\Sidulur\Models\Officer;
use Bale\Sidulur\Services\DocumentService;
use Bale\Sidulur\SidulurPermissions;
use Bale\Sidulur\Support\CitizenTable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

require_once __DIR__.'/../helpers.php';

uses()->beforeEach(sidulurTenant());

/**
 * Halaman Sidulur memanggil TenantConnectionService::ensureActive(), yang
 * menuntut koneksi aktif bernama `bale_<uuid>`, sesi tenant, dan baris
 * bale_users. Helper ini menyiapkan ketiganya di atas koneksi SQLite baru.
 */
beforeEach(function () {
    $baleId = '11111111-1111-1111-1111-111111111111';
    $connection = TenantManager::connectionName($baleId);

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

    BaleUser::create(['bale_id' => $baleId, 'user_uuid' => 'user-log-test']);
    session(['bale_active_uuid' => $baleId]);

    $property = new ReflectionProperty(TenantManager::class, 'activeConnection');
    $property->setAccessible(true);
    $property->setValue(null, $connection);
});

function sidulurLogUserCanOnly(string ...$allowed): void
{
    // Spatie menuntut causer berupa Model, jadi tiruannya harus model CMS.
    $user = Mockery::mock(User::class)->makePartial();
    $user->uuid = 'user-log-test';
    $user->shouldReceive('can')->andReturnUsing(fn (string $permission) => in_array($permission, $allowed, true));

    Auth::guard('web')->setUser($user);
}

it('mencatat aktivitas model sidulur ke activity log core', function () {
    $citizen = sidulurCitizen(sidulurFamilyCard());
    $citizen->update(['is_active' => false]);

    $logs = ActivityLog::query()
        ->where('subject_type', Citizen::class)
        ->where('subject_id', $citizen->getKey())
        ->orderBy('id')
        ->get();

    expect($logs->pluck('event')->all())->toContain('created')->toContain('updated')
        ->and($logs->first()->log_name)->toBe('sidulur')
        ->and($logs->first()->properties->get('tenant_id'))->toBe(TenantManager::getActiveConnection())
        ->and($logs->first()->causer_type)->toBeNull();
});

it('tidak menulis PII warga ke activity log', function () {
    $citizen = sidulurCitizen(sidulurFamilyCard());

    $log = ActivityLog::query()
        ->where('subject_type', Citizen::class)
        ->where('subject_id', $citizen->getKey())
        ->where('event', 'created')
        ->first();

    $attributes = array_keys($log->attribute_changes->get('attributes', []));

    expect($attributes)->toContain('family_card_id')
        ->not->toContain('nama')
        ->not->toContain('nik')
        ->not->toContain('phone')
        ->not->toContain('nrs');
});

it('mencatat pemulihan warga sebagai aktivitas restored', function () {
    $citizen = sidulurCitizen(sidulurFamilyCard());
    $citizen->delete();
    $citizen->restore();

    expect($citizen->fresh())->not->toBeNull()
        ->and(ActivityLog::query()
            ->where('subject_type', Citizen::class)
            ->where('subject_id', $citizen->getKey())
            ->pluck('event')->all())
        ->toContain('deleted')->toContain('restored');
});

it('menampilkan hanya warga terhapus pada halaman Data Terhapus', function () {
    $kk = sidulurFamilyCard();
    $active = sidulurCitizen($kk, '3502123456780001');
    $deleted = sidulurCitizen($kk, '3502123456780002', null);
    $deleted->delete();

    $builder = Citizen::query();
    CitizenTable::trashed($builder);

    expect($builder->pluck('id')->all())->toBe([$deleted->getKey()])
        ->and($builder->whereKey($active->getKey())->exists())->toBeFalse();
});

it('mencatat perubahan bidang petugas', function () {
    $connection = TenantManager::getActiveConnection();

    Schema::connection($connection)->create('users', function (Blueprint $table) {
        $table->uuid('uuid')->primary();
        $table->string('name');
        $table->string('email');
        $table->string('role');
    });

    $userId = '019cb27a-0000-7000-8000-000000000001';

    DB::connection($connection)->table('users')->insert([
        'uuid' => $userId, 'name' => 'Petugas', 'email' => 'petugas@example.test', 'role' => 'admin',
    ]);

    sidulurLogUserCanOnly(SidulurPermissions::OFFICER_CREATE);

    $bidang = sidulurBidang();

    Livewire::test(OfficerForm::class)
        ->set('user_id', $userId)
        ->set('is_bidang', true)
        ->set('bidang_ids', [$bidang->getKey()])
        ->call('save');

    $log = ActivityLog::query()
        ->where('subject_type', Officer::class)
        ->where('event', 'bidang')
        ->sole();

    expect($log->properties->get('attached'))->toBe([$bidang->getKey()])
        ->and($log->properties->get('detached'))->toBe([])
        ->and($log->properties->get('tenant_id'))->toBe(TenantManager::getActiveConnection())
        ->and($log->log_name)->toBe('sidulur');
});

it('mencatat pembukaan detail warga', function () {
    sidulurLogUserCanOnly(SidulurPermissions::CITIZEN_VIEW);

    $citizen = sidulurCitizen(sidulurFamilyCard());

    Livewire::test(CitizenShow::class, ['citizen' => $citizen]);

    $log = ActivityLog::query()
        ->where('subject_type', Citizen::class)
        ->where('event', 'dilihat')
        ->sole();

    expect($log->properties->get('tenant_id'))->toBe(TenantManager::getActiveConnection())
        ->and($log->properties->get('attributes'))->toBeEmpty();
});

it('mencatat akses dokumen warga', function () {
    Storage::fake('sidulur');
    Storage::disk('sidulur')->put('ktp.pdf', 'x');

    $citizen = sidulurCitizen(sidulurFamilyCard());
    $document = $citizen->documents()->create([
        'kind' => 'ktp', 'disk' => 'sidulur', 'path' => 'ktp.pdf',
        'original_name' => 'ktp.pdf', 'mime' => 'application/pdf', 'size' => 1,
    ]);

    sidulurLogUserCanOnly(SidulurPermissions::CITIZEN_VIEW);

    $request = Request::create('/cms/sidulur/media/'.$document->getKey());
    $request->setUserResolver(fn () => auth()->user());

    $response = (new DocumentController)($request, $document->getKey(), app(DocumentService::class));

    $log = ActivityLog::query()
        ->where('subject_type', Document::class)
        ->where('subject_id', $document->getKey())
        ->where('event', 'diakses')
        ->sole();

    expect($response->getStatusCode())->toBe(200)
        ->and($log->properties->get('tenant_id'))->toBe(TenantManager::getActiveConnection());
});

it('mencatat semua model sidulur dengan log name sidulur', function () {
    $models = array_map(
        fn (string $file) => 'Bale\\Sidulur\\Models\\'.basename($file, '.php'),
        glob(dirname(__DIR__, 2).'/src/Models/*.php'),
    );

    expect($models)->not->toBeEmpty();

    foreach ($models as $model) {
        expect(class_uses_recursive($model))->toContain(LogsActivity::class)
            ->and((new $model)->getActivitylogOptions()->logName)->toBe('sidulur');
    }
});
