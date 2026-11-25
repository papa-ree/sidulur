<?php

use App\Models\User;
use Bale\Sidulur\Models\Officer;
use Bale\Sidulur\Services\OfficerScope;
use Bale\Sidulur\SidulurPermissions;
use Illuminate\Support\Facades\Gate;

require_once __DIR__.'/../helpers.php';

uses()->beforeEach(sidulurTenant());

function sidulurOfficerUser(bool $admin = false): User
{
    $user = Mockery::mock(User::class)->makePartial();
    $user->uuid = '019cb27a-0000-7000-8000-000000000001';
    $user->shouldReceive('hasAnyRole')->andReturn($admin);
    $user->shouldReceive('checkPermissionTo')->andReturnTrue();

    return $user;
}

it('meloloskan admin tanpa baris petugas', function () {
    expect(OfficerScope::grants(SidulurPermissions::SETTING_UPDATE, sidulurOfficerUser(true)))->toBeTrue();
});

it('menolak user tanpa baris petugas aktif', function () {
    $user = sidulurOfficerUser();

    expect(OfficerScope::grants(SidulurPermissions::PENDAFTARAN_VIEW, $user))->toBeFalse();

    Officer::query()->create(['user_id' => $user->uuid, 'is_pendaftaran' => true, 'is_active' => false]);

    expect(OfficerScope::grants(SidulurPermissions::PENDAFTARAN_VIEW, $user))->toBeFalse();
});

it('hanya membuka permission milik bendera yang dicentang', function () {
    $user = sidulurOfficerUser();

    Officer::query()->create(['user_id' => $user->uuid, 'is_pendaftaran' => true, 'is_active' => true]);

    expect(OfficerScope::grants(SidulurPermissions::PENDAFTARAN_VIEW, $user))->toBeTrue()
        ->and(OfficerScope::grants(SidulurPermissions::VERLAP_VIEW, $user))->toBeFalse();
});

it('menolak akses bidang sampai bidang petugas dipilih', function () {
    $user = sidulurOfficerUser();

    Officer::query()->create(['user_id' => $user->uuid, 'is_bidang' => true, 'is_active' => true]);

    expect(OfficerScope::grants(SidulurPermissions::PELAYANAN_VIEW, $user))->toBeFalse();

    $officer = Officer::query()->where('user_id', $user->uuid)->firstOrFail();
    $officer->bidangs()->sync([sidulurBidang()->id]);

    expect(OfficerScope::grants(SidulurPermissions::PELAYANAN_VIEW, $user))->toBeTrue();
});

it('memotong hasil gate sesuai bendera petugas', function () {
    $user = sidulurOfficerUser();

    Officer::query()->create(['user_id' => $user->uuid, 'is_pendaftaran' => true, 'is_active' => true]);

    expect(Gate::forUser($user)->allows(SidulurPermissions::PENDAFTARAN_VIEW))->toBeTrue()
        ->and(Gate::forUser($user)->allows(SidulurPermissions::VERLAP_VIEW))->toBeFalse();
});
