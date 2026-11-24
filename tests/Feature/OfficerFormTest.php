<?php

use Bale\Sidulur\Livewire\Pages\Officer\Form as OfficerForm;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require_once __DIR__.'/../helpers.php';

uses()->beforeEach(sidulurTenant());

it('menyembunyikan user root dari pilihan petugas', function () {
    Schema::connection('sidulur_test')->create('users', function (Blueprint $table) {
        $table->uuid('uuid')->primary();
        $table->string('name');
        $table->string('email');
        $table->string('role');
    });

    DB::connection('sidulur_test')->table('users')->insert([
        ['uuid' => '019cb27a-0000-7000-8000-000000000001', 'name' => 'Admin Bale', 'email' => 'admin@example.test', 'role' => 'admin'],
        ['uuid' => '019cb27a-0000-7000-8000-000000000002', 'name' => 'Root Bale', 'email' => 'root@example.test', 'role' => 'root'],
    ]);

    $users = (new OfficerForm)->render()->getData()['users'];

    expect($users->pluck('name')->all())->toBe(['Admin Bale']);
});
