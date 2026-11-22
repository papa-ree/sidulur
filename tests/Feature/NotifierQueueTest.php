<?php

use Bale\Sidulur\Jobs\SendSidulurWhatsapp;
use Bale\Sidulur\Models\Pendaftaran;
use Bale\Sidulur\Services\Notifier;
use Illuminate\Support\Facades\Queue;

require_once __DIR__.'/../helpers.php';

uses()->beforeEach(sidulurTenant());

it('mengantrekan notifikasi tiket alih-alih mengirim saat request', function () {
    Queue::fake();

    $citizen = sidulurCitizen(sidulurFamilyCard());
    $pendaftaran = new Pendaftaran(['ticket_number' => 'SDLR-0001']);

    (new Notifier)->ticketCreated($citizen, $pendaftaran);

    Queue::assertPushed(SendSidulurWhatsapp::class, fn (SendSidulurWhatsapp $job) => $job->phone === $citizen->phone
        && str_contains($job->message, 'SDLR-0001'));
});
