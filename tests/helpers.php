<?php

use Bale\Cms\Services\TenantManager;
use Bale\Sidulur\Models\Bidang;
use Bale\Sidulur\Models\Citizen;
use Bale\Sidulur\Models\FamilyCard;
use Bale\Sidulur\Models\Form;
use Bale\Sidulur\Models\Service;
use Bale\Sidulur\Services\Notifier;
use Illuminate\Support\Facades\DB;

/**
 * Aktifkan koneksi tenant SQLite in-memory dan jalankan migrasi Sidulur.
 *
 * Model Sidulur memakai UsesTenantConnection, jadi TenantManager harus
 * menunjuk koneksi ini sebelum query pertama.
 */
function sidulurTenant(): Closure
{
    return function () {
        config()->set('database.connections.sidulur_test', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        config()->set('sidulur.index_key', 'kunci-index-test-yang-berbeda-dari-app-key');

        DB::purge('sidulur_test');

        TenantManager::clear();

        $property = new ReflectionProperty(TenantManager::class, 'activeConnection');
        $property->setAccessible(true);
        $property->setValue(null, 'sidulur_test');

        $original = DB::getDefaultConnection();
        DB::setDefaultConnection('sidulur_test');
        config()->set('database.default', 'sidulur_test');

        foreach (glob(database_path('migrations/sidulur/*.php')) as $file) {
            (require $file)->up();
        }

        DB::setDefaultConnection($original);
        config()->set('database.default', $original);

        app()->instance(Notifier::class, new class extends Notifier
        {
            protected function send(Citizen $citizen, string $message): void
            {
                // Tidak mengirim WhatsApp pada test.
            }
        });
    };
}

function sidulurBidang(string $nama = 'Pelayanan Sosial'): Bidang
{
    return Bidang::query()->create(['nama' => $nama, 'icon' => 'landmark']);
}

function sidulurFamilyCard(string $nomor = '3502123456780001'): FamilyCard
{
    return FamilyCard::query()->create([
        'nomor_kk' => $nomor,
        'district_code' => '3502',
        'district_name' => 'Ponorogo',
        'village_code' => '3502010001',
        'village_name' => 'Banyudono',
        'alamat' => 'Jl. Contoh No. 1',
    ]);
}

function sidulurCitizen(FamilyCard $kk, string $nik = '3502123456780001', ?string $phone = '081234567890'): Citizen
{
    return Citizen::query()->create([
        'nama' => 'Warga Contoh',
        'nik' => $nik,
        'phone' => $phone,
        'family_card_id' => $kk->getKey(),
    ]);
}

function sidulurService(Bidang $bidang, array $overrides = []): Service
{
    static $counter = 0;
    $counter++;

    return Service::query()->create(array_merge([
        'bidang_id' => $bidang->getKey(),
        'nama' => 'Layanan '.$counter,
        'slug' => 'layanan-'.$counter,
        'icon' => 'clipboard-list',
    ], $overrides));
}

function sidulurForm(Service $service, array $schema = []): Form
{
    return Form::query()->create([
        'service_id' => $service->getKey(),
        'nama' => 'Form '.$service->nama,
        'schema' => $schema ?: [
            'persyaratan' => [['kode' => 'ktp', 'nama' => 'KTP', 'sumber' => 'ktp']],
            'fields' => [['key' => 'catatan', 'label' => 'Catatan', 'type' => 'textarea']],
            'verifikasi' => [['key' => 'kondisi_rumah', 'label' => 'Kondisi rumah']],
        ],
    ]);
}
