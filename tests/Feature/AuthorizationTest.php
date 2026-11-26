<?php

use Bale\Sidulur\Livewire\Pages\Citizen\Form as CitizenForm;
use Bale\Sidulur\Livewire\Pages\Citizen\Trashed as CitizenTrashed;
use Bale\Sidulur\Livewire\Pages\Form\Builder;
use Bale\Sidulur\Livewire\Pages\Pelayanan\Show as PelayananShow;
use Bale\Sidulur\Livewire\Pages\Pendaftaran\Create as PendaftaranCreate;
use Bale\Sidulur\Livewire\Pages\Setting\Index as SettingIndex;
use Bale\Sidulur\Livewire\Pages\Verlap\Show as VerlapShow;
use Bale\Sidulur\Services\CitizenRegistrar;
use Bale\Sidulur\Services\DocumentService;
use Bale\Sidulur\Services\SubmissionFlow;
use Bale\Sidulur\SidulurPermissions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Symfony\Component\HttpKernel\Exception\HttpException;

require_once __DIR__.'/../helpers.php';

/**
 * Pasang user tiruan yang selalu menolak izin.
 */
function sidulurUserDenied(): void
{
    $user = Mockery::mock(Authenticatable::class);
    $user->shouldReceive('can')->andReturnFalse();
    $user->shouldReceive('getAuthIdentifierName')->andReturn('id');
    $user->shouldReceive('getAuthIdentifier')->andReturn(1);

    Auth::guard('web')->setUser($user);
}

/**
 * Hanya izin tertentu yang diberikan; sisanya ditolak.
 */
function sidulurUserCanOnly(string ...$allowed): void
{
    $user = Mockery::mock(Authenticatable::class);
    $user->shouldReceive('can')->andReturnUsing(fn (string $permission) => in_array($permission, $allowed, true));
    $user->shouldReceive('getAuthIdentifierName')->andReturn('id');
    $user->shouldReceive('getAuthIdentifier')->andReturn(1);

    Auth::guard('web')->setUser($user);
}

/**
 * Jalankan aksi dan kembalikan status HTTP dari abort(); 0 berarti tidak ditolak.
 */
function sidulurStatus(callable $callback): int
{
    try {
        $callback();
    } catch (HttpException $e) {
        return $e->getStatusCode();
    }

    return 0;
}

it('menolak simpan warga tanpa izin', function () {
    sidulurUserDenied();

    $status = sidulurStatus(fn () => (new CitizenForm)->save(app(CitizenRegistrar::class)));

    expect($status)->toBe(403);
});

it('menolak edit warga bagi pemegang izin tambah saja', function () {
    sidulurUserCanOnly(SidulurPermissions::CITIZEN_CREATE);

    $component = new CitizenForm;
    $component->citizenId = '019cb27a-0000-7000-8000-000000000001';

    $status = sidulurStatus(fn () => $component->save(app(CitizenRegistrar::class)));

    expect($status)->toBe(403);
});

it('mengunci citizenId agar tidak bisa diganti dari klien', function () {
    $property = new ReflectionProperty(CitizenForm::class, 'citizenId');

    expect($property->getAttributes(Locked::class))->not->toBeEmpty();
});

it('menolak pemulihan warga tanpa izin hapus', function () {
    sidulurUserDenied();

    expect(sidulurStatus(fn () => (new CitizenTrashed)->restoreCitizen('id-warga')))->toBe(403);
});

it('menolak seluruh aksi pendaftaran tanpa izin', function () {
    sidulurUserDenied();

    $component = new PendaftaranCreate;

    $actions = [
        fn () => $component->search(app(CitizenRegistrar::class)),
        fn () => $component->selectCitizen('id-warga'),
        fn () => $component->createCitizen(app(CitizenRegistrar::class)),
        fn () => $component->selectService('id-layanan'),
        fn () => $component->finishDesk(app(SubmissionFlow::class)),
        fn () => $component->forward(app(SubmissionFlow::class)),
    ];

    foreach ($actions as $action) {
        expect(sidulurStatus($action))->toBe(403);
    }
});

it('menolak perubahan schema formulir tanpa izin', function () {
    sidulurUserDenied();

    $component = new Builder;

    $actions = [
        fn () => $component->addRequirement(),
        fn () => $component->removeRequirement(0),
        fn () => $component->addField('fields'),
        fn () => $component->removeField('fields', 0),
        fn () => $component->resetForm(),
        fn () => $component->save(),
    ];

    foreach ($actions as $action) {
        expect(sidulurStatus($action))->toBe(403);
    }
});

it('menolak simpan pengaturan tanpa izin', function () {
    sidulurUserDenied();

    expect(sidulurStatus(fn () => (new SettingIndex)->save()))->toBe(403);
});

it('menolak simpan hasil verifikasi lapangan tanpa izin', function () {
    sidulurUserDenied();

    expect(sidulurStatus(fn () => (new VerlapShow)->save(app(SubmissionFlow::class))))->toBe(403);
});

it('menolak seluruh aksi pelayanan bidang tanpa izin', function () {
    sidulurUserDenied();

    $component = new PelayananShow;

    $actions = [
        fn () => $component->schedule(app(SubmissionFlow::class)),
        fn () => $component->finish(app(SubmissionFlow::class), app(DocumentService::class)),
        fn () => $component->uploadHasil(app(DocumentService::class)),
    ];

    foreach ($actions as $action) {
        expect(sidulurStatus($action))->toBe(403);
    }
});
