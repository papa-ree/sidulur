<?php

use Illuminate\Support\ViewErrorBag;

require_once __DIR__.'/../helpers.php';

uses()->beforeEach(sidulurTenant());

it('membatasi input cari warga sampai 16 digit', function () {
    $html = view('sidulur::livewire.pages.pendaftaran.create', [
        'step' => 1,
        'results' => [],
        'showNewCitizen' => false,
        'citizen' => null,
        'identityMissing' => [],
        'service' => null,
        'requirements' => [],
        'services' => collect(),
        'districts' => [],
        'villages' => [],
        'errors' => new ViewErrorBag,
    ])->render();

    expect($html)->toContain('x-mask="9999 9999 9999 9999"');
});
