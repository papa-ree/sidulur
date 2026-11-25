<?php

use Bale\Sidulur\Jobs\SendSidulurWhatsapp;
use Bale\Sidulur\Models\Pendaftaran;
use Bale\Sidulur\Services\Notifier;
use Bale\Vada\Models\VadaChannel;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

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

it('mengirim notifikasi lewat device yang di-assign ke tenant', function () {
    Queue::fake();

    session(['bale_active_uuid' => $baleId = (string) Str::uuid()]);

    VadaChannel::create([
        'bale_id' => $baleId,
        'device_id' => '628111@s.whatsapp.net',
        'is_active' => true,
    ]);

    $citizen = sidulurCitizen(sidulurFamilyCard());

    (new Notifier)->ticketCreated($citizen, new Pendaftaran(['ticket_number' => 'SDLR-0002']));

    Queue::assertPushed(SendSidulurWhatsapp::class, fn (SendSidulurWhatsapp $job) => $job->deviceId === '628111@s.whatsapp.net');
});

it('memakai device default bila tenant belum punya kanal', function () {
    Queue::fake();

    session(['bale_active_uuid' => (string) Str::uuid()]);

    $citizen = sidulurCitizen(sidulurFamilyCard());

    (new Notifier)->ticketCreated($citizen, new Pendaftaran(['ticket_number' => 'SDLR-0003']));

    Queue::assertPushed(SendSidulurWhatsapp::class, fn (SendSidulurWhatsapp $job) => $job->deviceId === null);
});
