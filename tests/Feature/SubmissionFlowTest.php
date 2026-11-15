<?php

use Bale\Sidulur\Models\PelayananBidang;
use Bale\Sidulur\Models\Pendaftaran;
use Bale\Sidulur\Models\Timeline;
use Bale\Sidulur\Services\SubmissionFlow;
use Bale\Sidulur\Support\TicketNumber;

require_once __DIR__.'/../helpers.php';

uses()->beforeEach(sidulurTenant());

function sidulurFlow(): SubmissionFlow
{
    return app(SubmissionFlow::class);
}

it('membuat pendaftaran baru beserta tiket dan snapshot form', function () {
    $bidang = sidulurBidang();
    $service = sidulurService($bidang);
    sidulurForm($service);
    $citizen = sidulurCitizen(sidulurFamilyCard());

    $pendaftaran = sidulurFlow()->register($citizen, $service, 'Bantuan sosial', ['catatan' => 'halo']);

    expect($pendaftaran->status)->toBe(Pendaftaran::STATUS_BARU)
        ->and($pendaftaran->ticket_number)->toMatch('/^SDLR-\d{8}-[A-Z2-9]{4}$/')
        ->and($pendaftaran->form_data)->toBe(['catatan' => 'halo'])
        ->and($pendaftaran->form_snapshot['fields'])->toHaveCount(1)
        ->and($pendaftaran->expires_at)->not->toBeNull();

    expect(Timeline::query()->where('pendaftaran_id', $pendaftaran->getKey())->count())->toBe(1);
});

it('menyelesaikan pendaftaran di meja pendaftaran', function () {
    $service = sidulurService(sidulurBidang());
    $pendaftaran = sidulurFlow()->register(sidulurCitizen(sidulurFamilyCard()), $service, null, []);

    sidulurFlow()->finishAtDesk($pendaftaran, 'Selesai di loket.');

    $pendaftaran->refresh();

    expect($pendaftaran->status)->toBe(Pendaftaran::STATUS_SELESAI)
        ->and($pendaftaran->completed_at)->not->toBeNull()
        ->and($pendaftaran->pelayananBidang()->exists())->toBeFalse();

    $last = Timeline::query()
        ->where('pendaftaran_id', $pendaftaran->getKey())
        ->where('note', 'Selesai di loket.')
        ->first();

    expect($last?->to_status)->toBe(Pendaftaran::STATUS_SELESAI);
});

it('meneruskan pendaftaran ke bidang dan menolak penerusan ganda', function () {
    $bidang = sidulurBidang();
    $service = sidulurService($bidang);
    $pendaftaran = sidulurFlow()->register(sidulurCitizen(sidulurFamilyCard()), $service, null, []);

    $pelayanan = sidulurFlow()->forward($pendaftaran);

    expect($pelayanan->bidang_id)->toBe($bidang->getKey())
        ->and($pelayanan->status)->toBe(PelayananBidang::STATUS_BARU)
        ->and($pelayanan->pendaftaran_id)->toBe($pendaftaran->getKey());

    $pendaftaran->refresh();

    expect($pendaftaran->status)->toBe(Pendaftaran::STATUS_SELESAI)
        ->and($pendaftaran->forwarded_at)->not->toBeNull();

    expect(Timeline::query()->where('pendaftaran_id', $pendaftaran->getKey())->count())->toBe(3);

    expect(fn () => sidulurFlow()->forward($pendaftaran))
        ->toThrow(RuntimeException::class);
});

it('menjadwalkan verifikasi lapangan hanya dari status baru', function () {
    $pendaftaran = sidulurFlow()->register(
        sidulurCitizen(sidulurFamilyCard()),
        sidulurService(sidulurBidang()),
        null,
        []
    );

    $pelayanan = sidulurFlow()->forward($pendaftaran);
    $tanggal = now()->addDays(3)->toDateString();

    sidulurFlow()->schedule($pelayanan, $tanggal, 'Datang pagi.');

    $pelayanan->refresh();

    expect($pelayanan->status)->toBe(PelayananBidang::STATUS_DIJADWALKAN)
        ->and($pelayanan->scheduled_at->toDateString())->toBe($tanggal)
        ->and($pelayanan->scheduled_note)->toBe('Datang pagi.');

    expect(fn () => sidulurFlow()->schedule($pelayanan, $tanggal))
        ->toThrow(RuntimeException::class);
});

it('mencatat hasil verifikasi lapangan hanya setelah dijadwalkan', function () {
    $service = sidulurService(sidulurBidang());
    sidulurForm($service);
    $pendaftaran = sidulurFlow()->register(sidulurCitizen(sidulurFamilyCard()), $service, null, []);

    $pelayanan = sidulurFlow()->forward($pendaftaran);

    expect(fn () => sidulurFlow()->recordVerlap($pelayanan, ['kondisi_rumah' => 'baik']))
        ->toThrow(RuntimeException::class);

    sidulurFlow()->schedule($pelayanan, now()->addDay()->toDateString());
    sidulurFlow()->recordVerlap($pelayanan, ['kondisi_rumah' => 'baik']);

    $pelayanan->refresh();

    expect($pelayanan->status)->toBe(PelayananBidang::STATUS_VERLAP_SELESAI)
        ->and($pelayanan->verlap_data)->toBe(['kondisi_rumah' => 'baik'])
        ->and($pelayanan->verlap_snapshot[0]['key'])->toBe('kondisi_rumah')
        ->and($pelayanan->verlap_snapshot[0]['label'])->toBe('Kondisi rumah')
        ->and($pelayanan->verlap_at)->not->toBeNull();
});

it('menyelesaikan pengajuan bidang dari status baru maupun setelah verlap', function () {
    $bidang = sidulurBidang();
    $service = sidulurService($bidang);

    $first = sidulurFlow()->forward(
        sidulurFlow()->register(sidulurCitizen(sidulurFamilyCard()), $service, null, [])
    );

    sidulurFlow()->finish($first, ['hasil' => 'disetujui']);

    expect($first->refresh()->status)->toBe(PelayananBidang::STATUS_SELESAI)
        ->and($first->form_data)->toBe(['hasil' => 'disetujui'])
        ->and($first->completed_at)->not->toBeNull();

    $second = sidulurFlow()->forward(
        sidulurFlow()->register(sidulurCitizen(sidulurFamilyCard('3502123456780002'), '3502123456780009', null), $service, null, [])
    );

    sidulurFlow()->schedule($second, now()->addDay()->toDateString());
    sidulurFlow()->recordVerlap($second, []);
    sidulurFlow()->finish($second, []);

    expect($second->refresh()->status)->toBe(PelayananBidang::STATUS_SELESAI);

    expect(fn () => sidulurFlow()->finish($second, []))
        ->toThrow(RuntimeException::class);
});

it('menolak penyelesaian saat pengajuan sudah dijadwalkan', function () {
    $pelayanan = sidulurFlow()->forward(
        sidulurFlow()->register(sidulurCitizen(sidulurFamilyCard()), sidulurService(sidulurBidang()), null, [])
    );

    sidulurFlow()->schedule($pelayanan, now()->addDay()->toDateString());

    expect(fn () => sidulurFlow()->finish($pelayanan, []))
        ->toThrow(RuntimeException::class);
});

describe('TicketNumber', function () {
    it('memakai format tanggal dan alfabet tanpa karakter ambigu', function () {
        $ticket = TicketNumber::generate();

        expect($ticket)->toMatch('/^SDLR-\d{8}-[A-Z2-9]{4}$/')
            ->and(substr($ticket, 5, 8))->toBe(now()->format('Ymd'));

        foreach (str_split(substr($ticket, 14)) as $char) {
            expect(TicketNumber::ALPHABET)->toContain($char);
        }
    });

    it('memberi masa berlaku dari konfigurasi', function () {
        config()->set('sidulur.ticket_expiry_days', 14);

        $expiry = TicketNumber::expiry();

        expect($expiry->isSameDay(now()->addDays(14)))->toBeTrue();
    });
});
