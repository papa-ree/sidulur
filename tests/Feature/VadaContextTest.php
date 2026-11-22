<?php

use Bale\Sidulur\Models\Pendaftaran;
use Bale\Sidulur\SidulurPermissions;
use Bale\Sidulur\Vada\SidulurContactContext;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

require_once __DIR__.'/../helpers.php';

uses()->beforeEach(sidulurTenant());

function vadaContextUserCanOnly(string ...$allowed): void
{
    $user = Mockery::mock(Authenticatable::class);
    $user->shouldReceive('can')->andReturnUsing(fn (string $permission) => in_array($permission, $allowed, true));
    $user->shouldReceive('getAuthIdentifierName')->andReturn('id');
    $user->shouldReceive('getAuthIdentifier')->andReturn(1);

    Auth::guard('web')->setUser($user);
}

it('mengembalikan identitas dan tiga pengajuan terbaru untuk nomor warga', function () {
    $kk = sidulurFamilyCard();
    $citizen = sidulurCitizen($kk, '3502123456780001', '081234567890');
    $service = sidulurService(sidulurBidang());

    foreach (range(1, 4) as $day) {
        $pendaftaran = Pendaftaran::query()->create([
            'ticket_number' => 'SDLR-000'.$day,
            'citizen_id' => $citizen->getKey(),
            'service_id' => $service->getKey(),
            'status' => $day === 4 ? Pendaftaran::STATUS_SELESAI : Pendaftaran::STATUS_BARU,
        ]);
        $pendaftaran->forceFill(['created_at' => now()->subDays(5 - $day)])->save();
    }

    vadaContextUserCanOnly(SidulurPermissions::CITIZEN_VIEW, SidulurPermissions::PENDAFTARAN_CREATE);

    $contexts = (new SidulurContactContext)->forPhone('6281234567890');

    expect($contexts)->toHaveCount(1);

    $context = $contexts[0];

    expect($context->matched())->toBeTrue()
        ->and($context->source)->toBe('Sidulur')
        ->and($context->name)->toBe('Warga Contoh')
        ->and($context->captionTone)->toBe('teal')
        ->and($context->fields)->toHaveKey('NIK')
        ->and($context->fields['NIK'])->not->toBe('3502123456780001')
        ->and($context->fields['Alamat'])->toContain('Banyudono')
        ->and($context->history)->toHaveCount(3)
        ->and($context->history[0]->title)->toBe($service->nama)
        ->and($context->history[0]->tone)->toBe('teal')
        ->and($context->history[1]->tone)->toBe('amber')
        ->and($context->actions)->toBe([])
        ->and($context->panelView)->toBe('sidulur::partials.chat.service-panel')
        ->and($context->panelData['phone'])->toBe('6281234567890')
        ->and($context->historyUrl)->toBe(route('sidulur.citizen.show', $citizen->getKey()));
});

it('menandai nomor yang belum terdaftar sebagai warga', function () {
    vadaContextUserCanOnly(SidulurPermissions::CITIZEN_VIEW, SidulurPermissions::CITIZEN_CREATE);

    $context = (new SidulurContactContext)->forPhone('628999999999')[0];

    expect($context->matched())->toBeFalse()
        ->and($context->emptyMessage)->toBe('Nomor ini belum terdaftar sebagai warga.')
        ->and($context->fallbackAction['url'])->toContain('phone=628999999999');
});

it('menyembunyikan data warga tanpa izin tetapi panel tetap ada', function () {
    sidulurCitizen(sidulurFamilyCard(), '3502123456780001', '081234567890');

    vadaContextUserCanOnly(SidulurPermissions::VIEW);

    $context = (new SidulurContactContext)->forPhone('6281234567890')[0];

    expect($context->matched())->toBeFalse()
        ->and($context->emptyMessage)->toContain('Butuh izin Data Warga')
        ->and($context->fallbackAction)->toBeNull();
});

it('tidak menawarkan tambah layanan tanpa izin pendaftaran', function () {
    sidulurCitizen(sidulurFamilyCard(), '3502123456780001', '081234567890');

    vadaContextUserCanOnly(SidulurPermissions::CITIZEN_VIEW);

    $context = (new SidulurContactContext)->forPhone('081234567890')[0];

    expect($context->matched())->toBeTrue()
        ->and($context->name)->toBe('Warga Contoh')
        ->and($context->actions)->toBe([])
        ->and($context->panelView)->toBeNull()
        ->and($context->history)->toBe([]);
});

it('memasang halaman chat sidulur di atas komponen vada', function () {
    expect(Route::has('sidulur.chat.index'))->toBeTrue()
        ->and(Livewire::exists('vada.shared-components.chat.inbox'))->toBeTrue();
});
