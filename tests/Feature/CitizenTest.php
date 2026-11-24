<?php

use Bale\Core\Support\Cdn;
use Bale\Sidulur\Livewire\Pages\Citizen\Form as CitizenForm;
use Bale\Sidulur\Livewire\Pages\Pendaftaran\Create as PendaftaranCreate;
use Bale\Sidulur\Models\Citizen;
use Bale\Sidulur\Models\Document;
use Bale\Sidulur\Models\FamilyCard;
use Bale\Sidulur\Services\CitizenRegistrar;
use Bale\Sidulur\Services\DocumentService;
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

it('mengubah gambar dokumen menjadi WebP dan membiarkan PDF apa adanya', function () {
    Storage::fake('sidulur');

    $citizen = sidulurCitizen(sidulurFamilyCard());
    $documents = app(DocumentService::class);

    $image = $documents->upload($citizen, UploadedFile::fake()->image('ktp.jpg', 2400, 1600), 'ktp');
    $pdf = $documents->upload($citizen, UploadedFile::fake()->create('kartu-keluarga.pdf', 100, 'application/pdf'), 'kk');

    expect($image->mime)->toBe('image/webp')
        ->and($image->path)->toEndWith('.webp')
        ->and($image->original_name)->toBe('ktp.webp')
        ->and($image->size)->toBeGreaterThan(0)
        ->and(Storage::disk('sidulur')->exists($image->path))->toBeTrue()
        ->and($pdf->mime)->toBe('application/pdf')
        ->and($pdf->path)->toEndWith('.pdf')
        ->and($pdf->original_name)->toBe('kartu-keluarga.pdf');
});

it('menyiapkan pratinjau dokumen sesuai jenis berkas', function () {
    Storage::fake('sidulur');

    $citizen = sidulurCitizen(sidulurFamilyCard());
    $documents = app(DocumentService::class);

    $image = $documents->upload($citizen, UploadedFile::fake()->image('ktp.jpg'), 'ktp');
    $pdf = $documents->upload($citizen, UploadedFile::fake()->create('kartu-keluarga.pdf', 100, 'application/pdf'), 'kk');

    expect($documents->preview(null))->toBeNull()
        ->and($documents->preview($image)['is_image'])->toBeTrue()
        ->and($documents->preview($image)['url'])->toContain($image->getKey())
        ->and($documents->preview($pdf)['is_image'])->toBeFalse()
        ->and($documents->preview($pdf)['name'])->toBe('kartu-keluarga.pdf');

    // Production: pratinjau lewat CDN core, bukan route media.
    $this->app['env'] = 'production';
    config(['core.cdn.enabled' => true, 'core.cdn.base_url' => 'https://cdn.test', 'core.cdn.prefix' => 'bale']);
    Cdn::forgetOrganizationSlug();

    expect($documents->preview($image)['url'])->toBe('https://cdn.test/bale/'.$image->path);
});

it('membolehkan beberapa warga berbagi satu nomor HP', function () {
    $card = sidulurFamilyCard();

    sidulurCitizen($card, '3502123456780001', '081234567890');
    sidulurCitizen($card, '3502123456780002', '081234567890');

    expect(app(CitizenRegistrar::class)->search('081234567890'))->toHaveCount(2)
        ->and(Citizen::query()->where('phone_hash', BlindIndex::make('6281234567890'))->count())->toBe(2);
});

it('menolak warga keenam pada satu nomor HP', function () {
    $card = sidulurFamilyCard();

    foreach (range(1, 5) as $i) {
        sidulurCitizen($card, '350212345678000'.$i, '081234567890');
    }

    $register = fn () => app(CitizenRegistrar::class)->register([
        'nama' => 'Warga Keenam',
        'nik' => '3502123456789999',
        'phone' => '081234567890',
        'nomor_kk' => '3502123456780001',
        'district_code' => '3502',
        'village_code' => '3502010001',
        'alamat' => 'Jl. Contoh No. 1',
    ]);

    expect($register)->toThrow(ValidationException::class);

    try {
        $register();
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('phone');
    }

    expect(Citizen::query()->count())->toBe(5);
});

it('menolak memindahkan warga ke nomor HP yang sudah penuh', function () {
    $card = sidulurFamilyCard();

    foreach (range(1, 5) as $i) {
        sidulurCitizen($card, '350212345678000'.$i, '081234567890');
    }

    $other = sidulurCitizen(sidulurFamilyCard('3502123456780099'), '3502123456780099', '081200000000');

    expect(fn () => app(CitizenRegistrar::class)->register([
        'nama' => 'Warga Pindah',
        'nik' => '3502123456780099',
        'phone' => '081234567890',
        'nomor_kk' => '3502123456780001',
        'district_code' => '3502',
        'village_code' => '3502010001',
        'alamat' => 'Jl. Contoh No. 1',
    ], null, null, $other))->toThrow(ValidationException::class);
});

it('tidak menghitung warga itu sendiri saat menyimpan nomor yang sama', function () {
    $card = sidulurFamilyCard();
    $citizen = null;

    foreach (range(1, 5) as $i) {
        $record = sidulurCitizen($card, '350212345678000'.$i, '081234567890');

        $citizen ??= $record;
    }

    app(CitizenRegistrar::class)->register([
        'nama' => 'Warga Contoh',
        'nik' => '3502123456780001',
        'phone' => '081234567890',
        'nomor_kk' => '3502123456780001',
        'district_code' => '3502',
        'village_code' => '3502010001',
        'alamat' => 'Jl. Contoh No. 1',
    ], null, null, $citizen);

    expect(Citizen::query()->where('phone_hash', BlindIndex::make('6281234567890'))->count())->toBe(5);
});

it('menemukan dokumen KK dari nomor KK yang sudah terdaftar', function () {
    sidulurBerkas(sidulurCitizen(sidulurFamilyCard()));

    expect(FamilyCard::kkDocument('3502123456780001'))->not->toBeNull()
        ->and(FamilyCard::kkDocument('3502123456780099'))->toBeNull();
});

it('melepas kewajiban unggah KK bila kartu sudah punya KK', function () {
    sidulurBerkas(sidulurCitizen(sidulurFamilyCard()));

    $warga = new CitizenForm;
    $warga->nomor_kk = '3502123456780001';
    $warga->updatedNomorKk();

    $pendaftaran = new PendaftaranCreate;
    $pendaftaran->nomor_kk = '3502123456780001';
    $pendaftaran->updatedNomorKk();

    expect($warga->existing['kk'])->not->toBeNull()
        ->and($pendaftaran->existingKk)->not->toBeNull();

    // Nomor KK lain tanpa dokumen → unggah KK kembali wajib.
    $warga->nomor_kk = '3502123456780099';
    $warga->updatedNomorKk();

    expect($warga->existing['kk'])->toBeNull();
});
