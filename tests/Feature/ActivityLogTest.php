<?php

use Bale\Core\Models\ActivityLog;
use Bale\Sidulur\Models\Citizen;
use Bale\Sidulur\Support\CitizenTable;

require_once __DIR__.'/../helpers.php';

uses()->beforeEach(sidulurTenant());

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
        ->and($logs->first()->properties->get('tenant_id'))->toBe('sidulur_test')
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
