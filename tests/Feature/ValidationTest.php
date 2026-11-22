<?php

use Illuminate\Support\Facades\Validator;

it('memakai pesan validasi bahasa indonesia', function () {
    app()->setLocale('id');

    $validator = Validator::make(['formData' => 'teks'], [
        'nama' => 'required',
        'nik' => 'required|digits:16',
        'alamat' => 'max:5',
        'formData' => 'array',
    ]);

    expect($validator->errors()->first('nama'))->toBe('Nama wajib diisi.')
        ->and($validator->errors()->first('nik'))->toBe('NIK wajib diisi.')
        ->and($validator->errors()->first('formData'))->toBe('Data formulir tidak valid.');

    $tooLong = Validator::make(['alamat' => 'terlalu panjang'], ['alamat' => 'max:5']);

    expect($tooLong->errors()->first('alamat'))->toBe('Isian Alamat maksimal 5 karakter.');
});
