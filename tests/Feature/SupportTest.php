<?php

use Bale\Sidulur\Support\BlindIndex;
use Bale\Sidulur\Support\FormSchema;

require_once __DIR__.'/../helpers.php';

describe('BlindIndex', function () {
    beforeEach(sidulurTenant());

    it('menormalkan NIK, KK, dan nomor HP dengan aturan yang sama', function () {
        expect(BlindIndex::normalize('3502 1234 5678 0001'))->toBe('3502123456780001')
            ->and(BlindIndex::normalize('0812-3456-7890'))->toBe('6281234567890')
            ->and(BlindIndex::normalize('812 3456 7890'))->toBe('6281234567890')
            ->and(BlindIndex::normalize('+62 812 3456 7890'))->toBe('6281234567890')
            ->and(BlindIndex::normalize(''))->toBeNull()
            ->and(BlindIndex::normalize(null))->toBeNull()
            ->and(BlindIndex::normalize('abc'))->toBeNull();
    });

    it('menghasilkan hash deterministik dengan kunci konfigurasi', function () {
        $first = BlindIndex::make('0812-3456-7890');

        expect($first)->toBe(BlindIndex::make('6281234567890'))
            ->and($first)->toHaveLength(64)
            ->and(BlindIndex::make(''))->toBeNull();

        config()->set('sidulur.index_key', 'kunci-lain');

        expect(BlindIndex::make('081234567890'))->not->toBe($first);
    });
});

describe('FormSchema', function () {
    it('mengisi nilai bawaan, menyaring baris kosong, dan mengurutkan', function () {
        $schema = FormSchema::normalize([
            'fields' => [
                ['key' => 'kedua', 'label' => 'Kedua', 'order' => 2],
                ['label' => ''],
                'bukan-array',
                ['label' => 'Pertama', 'type' => 'tidak-dikenal', 'wajib' => 1, 'order' => 1],
            ],
        ]);

        expect($schema['fields'])->toHaveCount(2)
            ->and($schema['fields'][0]['label'])->toBe('Pertama')
            ->and($schema['fields'][0]['type'])->toBe('text')
            ->and($schema['fields'][0]['required'])->toBeTrue()
            ->and($schema['fields'][0]['key'])->toBe('field_2')
            ->and($schema['fields'][1]['key'])->toBe('kedua')
            ->and($schema['persyaratan'])->toBe([])
            ->and($schema['verifikasi'])->toBe([]);
    });

    it('menandai persyaratan master yang sudah tersedia', function () {
        $schema = FormSchema::normalize([
            'persyaratan' => [
                ['kode' => 'ktp', 'nama' => 'KTP', 'sumber' => 'ktp'],
                ['kode' => 'kk', 'nama' => 'KK', 'sumber' => 'kk'],
                ['kode' => 'hp', 'nama' => 'HP', 'sumber' => 'hp'],
                ['kode' => 'lain', 'nama' => 'Surat lain'],
            ],
        ]);

        expect(FormSchema::masterRequirements($schema, true, false, true))
            ->toBe(['ktp' => true, 'kk' => false, 'hp' => true]);
    });
});
