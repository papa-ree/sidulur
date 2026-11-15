<?php

use Bale\Sidulur\Models\Citizen;
use Bale\Sidulur\Models\Document;
use Bale\Sidulur\Models\FamilyCard;
use Bale\Sidulur\Services\CitizenRegistrar;
use Bale\Sidulur\Support\BlindIndex;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

require_once __DIR__.'/../helpers.php';

uses()->beforeEach(sidulurTenant());

it('menyimpan NIK dan HP terenkripsi beserta blind index', function () {
    $kk = sidulurFamilyCard();
    $citizen = sidulurCitizen($kk);

    $raw = $citizen->getAttributes();

    expect($raw['nik'])->not->toContain('3502123456780001')
        ->and($raw['phone'])->not->toContain('081234567890')
        ->and($raw['nik_hash'])->toBe(BlindIndex::make('3502123456780001'))
        ->and($raw['phone_hash'])->toBe(BlindIndex::make('6281234567890'))
        ->and($citizen->nik)->toBe('3502123456780001')
        ->and($citizen->phone)->toBe('6281234567890');
});

it('memberi NRS 8 digit yang unik tanpa ditampilkan', function () {
    $kk = sidulurFamilyCard();

    $first = sidulurCitizen($kk, '3502123456780001');
    $second = sidulurCitizen($kk, '3502123456780002', null);

    expect($first->nrs)->toHaveLength(8)
        ->and($first->nrs)->toMatch('/^\d{8}$/')
        ->and($second->nrs)->not->toBe($first->nrs)
        ->and($first->toArray())->not->toHaveKey('nrs');
});

it('menormalkan nomor KK dan menolak KK kosong', function () {
    $kk = FamilyCard::query()->create([
        'nomor_kk' => '3502 1234 5678 0001',
        'district_code' => '3502',
        'district_name' => 'Ponorogo',
        'village_code' => '3502010001',
        'village_name' => 'Banyudono',
        'alamat' => 'Jl. Contoh',
    ]);

    expect($kk->nomor_kk)->toBe('3502123456780001')
        ->and($kk->nomor_kk_hash)->toBe(BlindIndex::make('3502123456780001'));

    expect(fn () => FamilyCard::query()->create([
        'nomor_kk' => '',
        'district_code' => '3502',
        'district_name' => 'Ponorogo',
        'village_code' => '3502010001',
        'village_name' => 'Banyudono',
        'alamat' => 'Jl. Contoh',
    ]))->toThrow(InvalidArgumentException::class);
});

it('mencari warga lewat NIK, HP, KK, dan nama', function () {
    $kk = sidulurFamilyCard();
    $citizen = sidulurCitizen($kk);

    $byNik = Citizen::query()->withIdentity('3502123456780001')->first();
    $byPhone = Citizen::query()->withIdentity('0812 3456 7890')->first();
    $byKk = Citizen::query()->withIdentity('3502123456780001')->first();
    $byName = Citizen::searchForTable(Citizen::query(), 'Warga', ['nama'])->first();

    expect($byNik?->is($citizen))->toBeTrue()
        ->and($byPhone?->is($citizen))->toBeTrue()
        ->and($byKk?->is($citizen))->toBeTrue()
        ->and($byName?->is($citizen))->toBeTrue()
        ->and(Citizen::searchForTable(Citizen::query(), 'tidak-ada', ['nama'])->count())->toBe(0);
});

it('menambahkan anggota keluarga pada KK yang sama tanpa membuat KK baru', function () {
    Storage::fake('sidulur');

    $card = sidulurFamilyCard('3502123456780009');

    $citizen = app(CitizenRegistrar::class)->register([
        'nama' => 'Kepala Keluarga',
        'nik' => '3502123456780001',
        'nomor_kk' => '3502123456780009',
        'district_code' => '3502',
        'village_code' => '3502010001',
        'alamat' => 'Jl. Contoh No. 1',
    ], null, null, null, [
        [
            'nik' => '3502123456780002',
            'nama' => 'Anggota Satu',
            'ktp' => UploadedFile::fake()->create('ktp-anggota.pdf', 100, 'application/pdf'),
            'kk' => UploadedFile::fake()->create('kk-anggota.pdf', 100, 'application/pdf'),
        ],
        ['nik' => '', 'nama' => '', 'ktp' => null, 'kk' => null],
    ]);

    $anggota = Citizen::query()->where('nik_hash', BlindIndex::make('3502123456780002'))->first();

    expect(FamilyCard::query()->count())->toBe(1)
        ->and(Citizen::query()->count())->toBe(2)
        ->and($anggota?->family_card_id)->toBe($card->getKey())
        ->and($anggota?->nama)->toBe('Anggota Satu')
        ->and($citizen->family_card_id)->toBe($card->getKey())
        ->and(Document::query()->where('kind', 'ktp')->count())->toBe(1)
        ->and(Document::query()->where('kind', 'kk')->count())->toBe(1)
        ->and(Document::query()->where('kind', 'kk')->first()?->documentable_id)->toBe($card->getKey());
});

it('menolak NIK anggota yang sudah terdaftar', function () {
    $card = sidulurFamilyCard();
    sidulurCitizen($card, '3502123456780005');

    $register = fn () => app(CitizenRegistrar::class)->register([
        'nama' => 'Kepala Keluarga',
        'nik' => '3502123456780001',
        'nomor_kk' => '3502123456780001',
        'district_code' => '3502',
        'village_code' => '3502010001',
        'alamat' => 'Jl. Contoh No. 1',
    ], null, null, null, [
        ['nik' => '3502123456780005', 'nama' => 'Duplikat'],
    ]);

    expect($register)->toThrow(ValidationException::class);

    try {
        $register();
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('anggota.0.nik');
    }

    expect(Citizen::query()->count())->toBe(1);
});
