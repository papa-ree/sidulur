<?php

use Bale\Sidulur\Models\Officer;
use Bale\Sidulur\Models\PelayananBidang;
use Bale\Sidulur\Services\SubmissionFlow;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require_once __DIR__.'/../helpers.php';

uses()->beforeEach(sidulurTenant());

it('mencari pelayanan berdasarkan nomor tiket atau nama warga', function () {
    $bidang = sidulurBidang();
    $service = sidulurService($bidang);
    sidulurForm($service);
    $pendaftaran = app(SubmissionFlow::class)
        ->register(sidulurCitizen(sidulurFamilyCard()), $service, []);
    app(SubmissionFlow::class)->forward($pendaftaran);

    $hit = fn (string $term) => PelayananBidang::searchForTable(
        PelayananBidang::query(), $term
    )->count();

    expect($hit($pendaftaran->ticket_number))->toBe(1)
        ->and($hit('Warga'))->toBe(1)
        ->and($hit('tidak-ada'))->toBe(0);
});

it('mencari petugas lewat nama atau email user tenant', function () {
    Schema::connection('sidulur_test')->create('users', function (Blueprint $table) {
        $table->uuid('uuid')->primary();
        $table->string('name');
        $table->string('email');
    });

    $user = ['uuid' => '019cb27a-0000-7000-8000-000000000001', 'name' => 'Siti Aminah', 'email' => 'siti@example.test'];

    DB::connection('sidulur_test')->table('users')->insert($user);
    Officer::query()->create(['user_id' => $user['uuid']]);

    $hit = fn (string $term) => Officer::searchForTable(Officer::query(), $term)->count();

    expect($hit('Aminah'))->toBe(1)
        ->and($hit('siti@example.test'))->toBe(1)
        ->and($hit('Budi'))->toBe(0);
});
