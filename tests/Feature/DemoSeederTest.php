<?php

use Bale\Sidulur\Models\Citizen;
use Bale\Sidulur\Models\Document;
use Bale\Sidulur\Models\FamilyCard;
use Bale\Sidulur\Models\PelayananBidang;
use Bale\Sidulur\Models\Pendaftaran;
use Bale\Sidulur\Models\Timeline;
use Bale\Sidulur\Services\DemoSeeder;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/../helpers.php';

uses()->beforeEach(sidulurTenant());

function sidulurDemoServices(): void
{
    $bidang = sidulurBidang();

    $slugs = [
        'pelayanan-informasi-konsultasi-bantuan-sosial',
        'usulan-pembaruan-dtsen',
        'fasilitasi-usulan-pkh',
        'bantuan-pangan-non-tunai',
        'pbi-jkn',
        'rekomendasi-biakesmaskin',
        'penerbitan-surat-keterangan-kks',
        'rekomendasi-baznas',
    ];

    foreach ($slugs as $slug) {
        sidulurForm(sidulurService($bidang, ['slug' => $slug]));
    }
}

it('mengisi data demo lengkap dari warga sampai verlap', function () {
    Storage::fake('sidulur');
    sidulurDemoServices();

    $summary = app(DemoSeeder::class)->run();

    expect($summary['kartu_keluarga'])->toBe(5)
        ->and($summary['warga'])->toBe(13)
        ->and($summary['pendaftaran'])->toBe(8)
        ->and($summary['password'])->toBe(DemoSeeder::DASHBOARD_PASSWORD);

    expect(Document::query()->where('kind', 'ktp')->count())->toBe(13)
        ->and(Document::query()->where('kind', 'kk')->count())->toBe(5)
        ->and(FamilyCard::query()->has('citizens')->count())->toBe(5);

    expect(Pendaftaran::query()->where('status', Pendaftaran::STATUS_BARU)->count())->toBe(1)
        ->and(Pendaftaran::query()->whereNotNull('forwarded_at')->count())->toBe(5)
        ->and(PelayananBidang::query()->distinct()->pluck('status')->sort()->values()->all())
        ->toBe(['baru', 'dijadwalkan', 'selesai', 'verlap_selesai']);

    // Setiap tiket punya riwayat untuk halaman tracking.
    expect(Timeline::query()->count())->toBeGreaterThan(10)
        ->and(Pendaftaran::query()->doesntHave('timelines')->count())->toBe(0);

    // Tanggal demo tersebar, bukan semuanya hari ini.
    expect(Pendaftaran::query()->whereDate('created_at', today())->count())->toBe(2);
});

it('tidak menggandakan data demo saat dijalankan ulang', function () {
    Storage::fake('sidulur');
    sidulurDemoServices();

    $seeder = app(DemoSeeder::class);
    $seeder->run();
    $second = $seeder->run();

    expect($second['pendaftaran'])->toBe(0)
        ->and($second['dokumen'])->toBe(0)
        ->and($second['password'])->toBeNull()
        ->and(Citizen::query()->count())->toBe(13)
        ->and(FamilyCard::query()->count())->toBe(5)
        ->and(Pendaftaran::query()->count())->toBe(8);
});
