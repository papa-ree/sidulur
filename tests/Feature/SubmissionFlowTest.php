<?php

use Bale\Sidulur\Livewire\Pages\Pelayanan\Show as PelayananShow;
use Bale\Sidulur\Models\PelayananBidang;
use Bale\Sidulur\Models\Pendaftaran;
use Bale\Sidulur\Models\Timeline;
use Bale\Sidulur\Services\DocumentService;
use Bale\Sidulur\Services\SubmissionFlow;
use Bale\Sidulur\Support\TicketNumber;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Livewire\Drawer\Utils;

require_once __DIR__.'/../helpers.php';

uses()->beforeEach(sidulurTenant());

function sidulurFlow(): SubmissionFlow
{
    return app(SubmissionFlow::class);
}

/**
 * User nyata (bukan mock) agar kolom `uuid` dan activity log tetap terbaca.
 */
function sidulurPelayananUser(): void
{
    Gate::before(fn () => true);

    $user = new User;
    $user->forceFill(['id' => 1, 'uuid' => 'user-uji']);

    Auth::guard('web')->setUser($user);
}

it('membuat pendaftaran baru beserta tiket dan snapshot form', function () {
    $bidang = sidulurBidang();
    $service = sidulurService($bidang);
    sidulurForm($service);
    $citizen = sidulurCitizen(sidulurFamilyCard());

    $pendaftaran = sidulurFlow()->register($citizen, $service, ['catatan' => 'halo']);

    expect($pendaftaran->status)->toBe(Pendaftaran::STATUS_BARU)
        ->and($pendaftaran->ticket_number)->toMatch('/^SDLR-\d{8}-[A-Z2-9]{4}$/')
        ->and($pendaftaran->form_data)->toBe(['catatan' => 'halo'])
        ->and($pendaftaran->form_snapshot['fields'])->toHaveCount(1)
        ->and($pendaftaran->expires_at)->not->toBeNull();

    expect(Timeline::query()->where('pendaftaran_id', $pendaftaran->getKey())->count())->toBe(1);
});

it('menyimpan berkas persyaratan sebagai dokumen pendaftaran', function () {
    Storage::fake('sidulur');

    $pendaftaran = sidulurFlow()->register(
        sidulurCitizen(sidulurFamilyCard()),
        sidulurService(sidulurBidang()),
        [],
        ['dokumen_1' => [UploadedFile::fake()->create('syarat.pdf', 100, 'application/pdf')]],
    );

    $documents = $pendaftaran->documents()->get();

    expect($documents)->toHaveCount(1)
        ->and($documents[0]->kind)->toBe('dokumen_1')
        ->and($documents[0]->original_name)->toBe('syarat.pdf');

    Storage::disk('sidulur')->assertExists($documents[0]->path);
});

it('menolak respon bidang sebelum verifikasi lapangan selesai', function () {
    $service = sidulurService(sidulurBidang(), ['butuh_verifikasi' => true]);
    $pelayanan = sidulurFlow()->forward(
        sidulurFlow()->register(sidulurCitizen(sidulurFamilyCard()), $service, [])
    );

    expect($pelayanan->bolehRespon())->toBeFalse()
        ->and($pelayanan->bolehUnggahHasil())->toBeFalse();
    expect(fn () => sidulurFlow()->finish($pelayanan, []))->toThrow(RuntimeException::class);

    sidulurFlow()->schedule($pelayanan, now()->addDay()->toDateString());
    $pelayanan->refresh();

    expect($pelayanan->bolehRespon())->toBeFalse()
        ->and($pelayanan->bolehUnggahHasil())->toBeFalse();
    expect(fn () => sidulurFlow()->finish($pelayanan, []))->toThrow(RuntimeException::class);

    sidulurFlow()->recordVerlap($pelayanan, []);
    $pelayanan->refresh();

    expect($pelayanan->bolehRespon())->toBeTrue()
        ->and($pelayanan->bolehUnggahHasil())->toBeTrue();

    sidulurFlow()->finish($pelayanan, []);

    expect($pelayanan->refresh()->status)->toBe(PelayananBidang::STATUS_SELESAI)
        ->and($pelayanan->bolehUnggahHasil())->toBeTrue();
});

it('membatasi unggah dokumen hasil untuk layanan tanpa verlap', function () {
    $pelayanan = sidulurFlow()->forward(
        sidulurFlow()->register(
            sidulurCitizen(sidulurFamilyCard()),
            sidulurService(sidulurBidang()),
            []
        )
    );

    expect($pelayanan->bolehRespon())->toBeTrue()
        ->and($pelayanan->bolehUnggahHasil())->toBeTrue();

    sidulurFlow()->finish($pelayanan, []);
    $pelayanan->refresh();

    expect($pelayanan->bolehRespon())->toBeFalse()
        ->and($pelayanan->bolehUnggahHasil())->toBeTrue();
});

it('mengganti dokumen hasil tanpa menyisakan berkas lama', function () {
    Storage::fake('sidulur');

    $pendaftaran = sidulurFlow()->register(sidulurCitizen(sidulurFamilyCard()), sidulurService(sidulurBidang()), []);
    $documents = app(DocumentService::class);

    $first = $documents->replace($pendaftaran, UploadedFile::fake()->create('hasil-1.pdf', 100, 'application/pdf'), 'hasil');
    $second = $documents->replace($pendaftaran, UploadedFile::fake()->create('hasil-2.pdf', 100, 'application/pdf'), 'hasil');

    expect($pendaftaran->documents()->where('kind', 'hasil')->pluck('id')->all())->toBe([$second->getKey()]);

    Storage::disk('sidulur')->assertMissing($first->path);
    Storage::disk('sidulur')->assertExists($second->path);
});

it('menyelesaikan pendaftaran di meja pendaftaran', function () {
    $service = sidulurService(sidulurBidang());
    $pendaftaran = sidulurFlow()->register(sidulurCitizen(sidulurFamilyCard()), $service, []);

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
    $pendaftaran = sidulurFlow()->register(sidulurCitizen(sidulurFamilyCard()), $service, []);

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
    $pendaftaran = sidulurFlow()->register(sidulurCitizen(sidulurFamilyCard()), $service, []);

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
        sidulurFlow()->register(sidulurCitizen(sidulurFamilyCard()), $service, [])
    );

    sidulurFlow()->finish($first, ['hasil' => 'disetujui']);

    expect($first->refresh()->status)->toBe(PelayananBidang::STATUS_SELESAI)
        ->and($first->form_data)->toBe(['hasil' => 'disetujui'])
        ->and($first->completed_at)->not->toBeNull();

    $second = sidulurFlow()->forward(
        sidulurFlow()->register(sidulurCitizen(sidulurFamilyCard('3502123456780002'), '3502123456780009', null), $service, [])
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
        sidulurFlow()->register(sidulurCitizen(sidulurFamilyCard()), sidulurService(sidulurBidang()), [])
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

it('menampilkan persyaratan pelayanan walau checklist belum diisi', function () {
    $service = sidulurService(sidulurBidang());
    sidulurForm($service, [
        'persyaratan' => [['kode' => 'dokumen_1', 'nama' => 'Surat Keterangan', 'type' => 'file']],
    ]);

    $pelayanan = sidulurFlow()->forward(
        sidulurFlow()->register(sidulurCitizen(sidulurFamilyCard()), $service, [])
    );

    $component = new PelayananShow;
    $component->pelayanan = $pelayanan->fresh();
    $component->requirements = []; // checklist petugas masih kosong

    // Livewire menimpa data view dengan properti publik setelah render().
    $view = $component->render();
    $view->with(Utils::getPublicPropertiesDefinedOnSubclass($component));

    expect($view->getData()['syarat'])->toHaveCount(1);
});

it('memisahkan bacaan dan form verifikasi ke tab berbeda', function () {
    $service = sidulurService(sidulurBidang());
    sidulurForm($service, [
        'persyaratan' => [['kode' => 'dokumen_1', 'nama' => 'Surat Keterangan', 'type' => 'file', 'wajib' => true]],
    ]);

    $pelayanan = sidulurFlow()->forward(
        sidulurFlow()->register(sidulurCitizen(sidulurFamilyCard()), $service, [])
    );

    sidulurPelayananUser();
    view()->share('errors', new ViewErrorBag);

    $component = new PelayananShow;
    $component->pelayanan = $pelayanan->fresh();
    $component->requirements = [];

    $view = $component->render();
    $view->with(Utils::getPublicPropertiesDefinedOnSubclass($component));
    $html = (string) $view->render();

    expect($html)->toContain('Bagian halaman')          // tab Ringkasan/Verifikasi
        ->and($html)->toContain("aktif = 'verifikasi'") // form ada di tab Verifikasi
        ->and($html)->toContain('0/1');                 // progres syarat wajib
});

it('memblokir penyelesaian pelayanan saat identitas warga belum lengkap', function () {
    $pelayanan = sidulurFlow()->forward(
        sidulurFlow()->register(sidulurCitizen(sidulurFamilyCard()), sidulurService(sidulurBidang()), [])
    );

    sidulurPelayananUser();

    $component = new PelayananShow;
    $component->pelayanan = $pelayanan->fresh();

    expect(fn () => $component->finish(sidulurFlow()))
        ->toThrow(ValidationException::class);
});

it('mewajibkan centang identitas sebelum pelayanan diselesaikan', function () {
    $citizen = sidulurCitizen(sidulurFamilyCard());
    sidulurBerkas($citizen);

    $pelayanan = sidulurFlow()->forward(
        sidulurFlow()->register($citizen, sidulurService(sidulurBidang()), [])
    );

    sidulurPelayananUser();

    $component = new PelayananShow;
    $component->pelayanan = $pelayanan->fresh();

    // Berkas lengkap tetapi petugas belum mencentang pemeriksaan.
    expect(fn () => $component->finish(sidulurFlow()))
        ->toThrow(ValidationException::class);

    $component->requirements = ['identitas' => true];
    $component->finish(sidulurFlow());

    expect($pelayanan->refresh()->status)->toBe(PelayananBidang::STATUS_SELESAI);
});
