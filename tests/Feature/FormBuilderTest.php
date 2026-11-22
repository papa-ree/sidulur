<?php

use Bale\Sidulur\Livewire\Pages\Form\Builder;
use Bale\Sidulur\Support\FormSchema;
use Illuminate\Support\ViewErrorBag;

require_once __DIR__.'/../helpers.php';

/**
 * Opsi field diedit sebagai teks berkoma dan baru dikonversi saat simpan.
 */
it('menyinkronkan teks opsi dengan array schema formulir', function () {
    $builder = new Builder;
    $builder->schema = [
        'persyaratan' => [],
        'fields' => [['key' => 'a', 'options' => ['Satu', 'Dua']]],
        'verifikasi' => [],
    ];

    $sync = new ReflectionMethod($builder, 'syncOptionsText');
    $sync->invoke($builder);

    expect($builder->optionsText)->toBe(['fields' => [0 => 'Satu,Dua']]);

    $builder->optionsText = ['fields' => [0 => ' Satu , , Dua ']];
    $apply = new ReflectionMethod($builder, 'applyOptions');
    $apply->invoke($builder);

    expect($builder->schema['fields'][0]['options'])->toBe(['Satu', 'Dua']);
});

it('membuat aturan required hanya untuk field yang ditandai wajib', function () {
    $fields = [
        ['key' => 'catatan', 'label' => 'Catatan', 'required' => true],
        ['key' => 'foto', 'label' => '', 'required' => false],
        ['key' => ''],
    ];

    expect(FormSchema::fieldRules($fields, 'formData'))->toBe([
        'formData.catatan' => ['required'],
        'formData.foto' => ['nullable'],
    ])->and(FormSchema::fieldLabels($fields, 'formData'))->toBe([
        'formData.catatan' => 'Catatan',
        'formData.foto' => 'foto',
    ]);
});

it('membuat aturan accepted hanya untuk persyaratan wajib', function () {
    $requirements = [
        ['kode' => 'ktp', 'nama' => 'KTP', 'wajib' => true],
        ['kode' => 'kk', 'nama' => 'KK', 'wajib' => false],
    ];

    expect(FormSchema::requirementRules($requirements))->toBe([
        'requirements.ktp' => ['accepted'],
        'requirements.kk' => ['nullable'],
    ])->and(FormSchema::requirementLabels($requirements))->toBe([
        'requirements.ktp' => 'KTP',
        'requirements.kk' => 'KK',
    ]);
});

it('menurunkan tipe berkas untuk persyaratan lama dan aturan isian per tipe', function () {
    $schema = FormSchema::normalize([
        'persyaratan' => [
            ['kode' => 'berkas', 'nama' => 'Berkas', 'wajib' => true],
            ['kode' => 'biaya', 'nama' => 'Biaya', 'wajib' => true, 'type' => 'number'],
            ['kode' => 'tanggal', 'nama' => 'Tanggal', 'type' => 'date'],
            ['kode' => 'uraian', 'nama' => 'Uraian', 'type' => 'textarea'],
        ],
    ]);

    expect(array_column($schema['persyaratan'], 'type'))->toBe(['file', 'number', 'date', 'textarea'])
        ->and($schema['persyaratan'][0])->not->toHaveKey('sumber')
        ->and(FormSchema::requirementValueRules($schema['persyaratan'], 'requirementData'))->toBe([
            'requirementData.biaya' => ['required', 'numeric'],
            'requirementData.tanggal' => ['nullable', 'date'],
            'requirementData.uraian' => ['nullable', 'string', 'max:2000'],
        ])
        ->and(FormSchema::requirementValueRules($schema['persyaratan'], 'requirementFiles', files: true))->toBe([
            'requirementFiles.berkas' => ['required', 'array', 'min:1', 'max:3'],
            'requirementFiles.berkas.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ]);
});

it('memasang x-mask pada isian dan persyaratan bertipe number', function () {
    view()->share('errors', new ViewErrorBag);

    $fields = view('sidulur::partials.dynamic-fields', [
        'fields' => [['key' => 'jumlah', 'label' => 'Jumlah', 'type' => 'number']],
        'modelPrefix' => 'formData',
    ])->render();

    $requirements = view('sidulur::partials.requirements', [
        'requirements' => [['key' => 'biaya', 'label' => 'Biaya', 'type' => 'number']],
        'dataModel' => 'requirementData',
        'filesModel' => 'requirementFiles',
    ])->render();

    expect($fields)->toContain('x-mask="999999999999"')
        ->and($requirements)->toContain('x-mask="999999999999"');
});

it('menolak syarat manual yang bentrok dengan identitas otomatis', function () {
    expect(FormSchema::reservedRequirement(['kode' => 'KTP', 'nama' => 'Kartu Identitas']))->toBe('kode')
        ->and(FormSchema::reservedRequirement(['kode' => 'dokumen_1', 'nama' => 'Kartu Keluarga']))->toBe('nama')
        ->and(FormSchema::reservedRequirement(['kode' => 'dokumen_2', 'nama' => 'Surat Keterangan']))->toBeNull();
});
