<?php

use Bale\Sidulur\Livewire\SharedComponents\Chat\ServiceForm;
use Bale\Sidulur\Models\Citizen;
use Bale\Sidulur\Models\Pendaftaran;
use Bale\Vada\Models\VadaConversation;
use Bale\Vada\Models\VadaMessage;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

require_once __DIR__.'/../helpers.php';

uses()->beforeEach(sidulurTenant());

uses()->beforeEach(function () {
    sidulurChatTables();

    Storage::fake('vada');
    Storage::fake('sidulur');
    config()->set('vada.media.disk', 'vada');
});

/**
 * Tabel Vada dibutuhkan komponen panel chat; migrasinya milik package vada.
 */
function sidulurChatTables(): void
{
    $original = DB::getDefaultConnection();
    DB::setDefaultConnection('sidulur_test');
    config()->set('database.default', 'sidulur_test');

    foreach (glob(base_path('packages/vada/database/migrations/tenant/*.php.stub')) as $file) {
        (require $file)->up();
    }

    DB::setDefaultConnection($original);
    config()->set('database.default', $original);
}

function sidulurChatUser(): void
{
    Gate::before(fn () => true);

    $user = new User;
    $user->forceFill(['id' => 1, 'uuid' => 'user-uji']);

    Auth::guard('web')->setUser($user);
}

function sidulurChatMedia(VadaConversation $conversation, string $name = 'surat.pdf'): VadaMessage
{
    $path = 'vada/tenant/'.$conversation->getKey().'/'.$name;

    Storage::disk('vada')->put($path, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n");

    return VadaMessage::query()->create([
        'conversation_id' => $conversation->getKey(),
        'direction' => VadaMessage::DIRECTION_IN,
        'body' => '',
        'status' => VadaMessage::STATUS_RECEIVED,
        'media_type' => 'document',
        'media_path' => $path,
        'media_name' => $name,
        'media_mime' => 'application/pdf',
        'media_size' => 10,
        'media_status' => VadaMessage::MEDIA_READY,
    ]);
}

function sidulurChatService()
{
    $service = sidulurService(sidulurBidang());

    sidulurForm($service, [
        'persyaratan' => [
            ['kode' => 'surat', 'nama' => 'Surat Permohonan', 'type' => 'file', 'wajib' => true],
        ],
        'fields' => [],
        'verifikasi' => [],
    ]);

    return $service;
}

it('mendaftarkan layanan dari panel chat dengan berkas dari media percakapan', function () {
    $citizen = sidulurCitizen(sidulurFamilyCard());
    sidulurBerkas($citizen);

    $service = sidulurChatService();
    $conversation = VadaConversation::forPhone('081234567890');
    $media = sidulurChatMedia($conversation);

    sidulurChatUser();

    Livewire::test(ServiceForm::class, ['phone' => '081234567890'])
        ->call('selectService', $service->getKey())
        ->set('requirementMedia.surat', $media->getKey())
        ->call('forward')
        ->assertHasNoErrors()
        ->assertDispatched('sidulur-service-closed')
        ->assertSet('ticket', fn ($value) => is_string($value) && str_starts_with($value, 'SDLR-'));

    $pendaftaran = Pendaftaran::query()->first();

    expect($pendaftaran)->not->toBeNull()
        ->and($pendaftaran->citizen_id)->toBe($citizen->getKey())
        ->and($pendaftaran->status)->toBe(Pendaftaran::STATUS_SELESAI)
        ->and($pendaftaran->pelayananBidang()->exists())->toBeTrue()
        ->and($pendaftaran->documents()->where('kind', 'surat')->count())->toBe(1);
})->group('sidulur-chat');

it('merender daftar layanan tanpa menunggu panel dibuka', function () {
    sidulurCitizen(sidulurFamilyCard());

    $service = sidulurChatService();
    VadaConversation::forPhone('081234567890');

    sidulurChatUser();

    Livewire::test(ServiceForm::class, ['phone' => '081234567890'])
        ->assertSee($service->nama);
})->group('sidulur-chat');

it('memblokir simpan dari panel chat bila identitas warga belum lengkap', function () {
    sidulurCitizen(sidulurFamilyCard());

    $service = sidulurChatService();
    VadaConversation::forPhone('081234567890');

    sidulurChatUser();

    Livewire::test(ServiceForm::class, ['phone' => '081234567890'])
        ->call('selectService', $service->getKey())
        ->call('forward')
        ->assertHasErrors('identitas');

    expect(Pendaftaran::query()->count())->toBe(0);
})->group('sidulur-chat');

it('merender form layanan di panel chat untuk pemegang izin pendaftaran', function () {
    $citizen = sidulurCitizen(sidulurFamilyCard());
    sidulurBerkas($citizen);
    VadaConversation::forPhone('081234567890');

    sidulurChatUser();

    $html = view('sidulur::partials.chat.service-panel', [
        'phone' => '081234567890',
        'variant' => 'column',
    ])->render();

    expect($html)->toContain('Tambah layanan');
})->group('sidulur-chat');

it('menolak media dari percakapan lain sebagai berkas persyaratan', function () {
    $citizen = sidulurCitizen(sidulurFamilyCard());
    sidulurBerkas($citizen);

    $service = sidulurChatService();
    VadaConversation::forPhone('081234567890');

    $other = VadaConversation::forPhone('081299999999');
    $media = sidulurChatMedia($other);

    sidulurChatUser();

    Livewire::test(ServiceForm::class, ['phone' => '081234567890'])
        ->call('selectService', $service->getKey())
        ->set('requirementMedia.surat', $media->getKey())
        ->call('forward')
        ->assertHasErrors('requirementFiles.surat');

    expect(Pendaftaran::query()->count())->toBe(0);
})->group('sidulur-chat');

it('langsung memakai warga tunggal tanpa memilih', function () {
    $citizen = sidulurCitizen(sidulurFamilyCard());
    VadaConversation::forPhone('081234567890');

    sidulurChatUser();

    Livewire::test(ServiceForm::class, ['phone' => '081234567890'])
        ->assertSet('citizenId', $citizen->getKey())
        ->assertDontSee('Pilih warga');
})->group('sidulur-chat');

it('meminta pilih warga dulu saat nomor dipakai beberapa warga', function () {
    $card = sidulurFamilyCard();

    sidulurBerkas(sidulurCitizen($card, '3502123456780001', '081234567890'));

    $second = Citizen::query()->create([
        'nama' => 'Sriyati',
        'nik' => '3502123456780002',
        'phone' => '081234567890',
        'family_card_id' => $card->getKey(),
    ]);
    sidulurBerkas($second);

    $service = sidulurService(sidulurBidang());
    sidulurForm($service, ['persyaratan' => [], 'fields' => [], 'verifikasi' => []]);

    VadaConversation::forPhone('081234567890');

    sidulurChatUser();

    $component = Livewire::test(ServiceForm::class, ['phone' => '081234567890'])
        ->assertSet('citizenId', null)
        ->assertSee('Pilih warga')
        ->call('forward')
        ->assertHasErrors('citizen');

    expect(Pendaftaran::query()->count())->toBe(0);

    $component->call('selectCitizen', $second->getKey())
        ->assertSet('citizenId', $second->getKey())
        ->call('selectService', $service->getKey())
        ->call('forward')
        ->assertHasNoErrors();

    expect(Pendaftaran::query()->first()?->citizen_id)->toBe($second->getKey());
})->group('sidulur-chat');

it('menolak memilih warga yang bukan pemakai nomor percakapan', function () {
    sidulurCitizen(sidulurFamilyCard(), '3502123456780001', '081234567890');

    $foreign = sidulurCitizen(sidulurFamilyCard('3502123456780099'), '3502123456780099', '081299999999');

    VadaConversation::forPhone('081234567890');

    sidulurChatUser();

    Livewire::test(ServiceForm::class, ['phone' => '081234567890'])
        ->call('selectCitizen', $foreign->getKey())
        ->assertStatus(404);
})->group('sidulur-chat');

it('hanya menawarkan media hari ini tetapi media terpilih tetap tampil', function () {
    sidulurBerkas(sidulurCitizen(sidulurFamilyCard()));

    $service = sidulurChatService();
    $conversation = VadaConversation::forPhone('081234567890');

    $today = sidulurChatMedia($conversation, 'hari-ini.pdf');
    $old = sidulurChatMedia($conversation, 'kemarin.pdf');
    VadaMessage::query()->whereKey($old->getKey())->update(['created_at' => now()->subDay()]);

    sidulurChatUser();

    $component = Livewire::test(ServiceForm::class, ['phone' => '081234567890'])
        ->call('selectService', $service->getKey())
        ->assertSee($today->media_name)
        ->assertDontSee($old->media_name);

    $component->set('requirementMedia.surat', $old->getKey())
        ->assertSee($old->media_name);
})->group('sidulur-chat');
