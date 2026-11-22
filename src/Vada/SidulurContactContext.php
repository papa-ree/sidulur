<?php

namespace Bale\Sidulur\Vada;

use Bale\Cms\Services\TenantManager;
use Bale\Sidulur\Models\Citizen;
use Bale\Sidulur\Models\Pendaftaran;
use Bale\Sidulur\SidulurPermissions;
use Bale\Sidulur\Support\BlindIndex;
use Bale\Vada\Contracts\ContactContextProvider;
use Bale\Vada\Support\ContactContext;
use Bale\Vada\Support\ContactHistoryItem;
use Illuminate\Support\Facades\Schema;

/**
 * Panel pemohon Vada untuk tenant Sidulur.
 *
 * Inilah seluruh ketergantungan Sidulur pada Vada: Vada tidak tahu apa pun
 * tentang warga, dan Sidulur mendaftarkan dirinya sendiri di service provider.
 */
class SidulurContactContext implements ContactContextProvider
{
    public function label(): string
    {
        return 'Sidulur';
    }

    public function forPhone(string $phone): array
    {
        $user = auth()->user();

        if ($user === null) {
            return [];
        }

        // Tenant ini punya Vada tetapi belum menjalankan migrasi Sidulur.
        $connection = TenantManager::getActiveConnection();

        if (! $connection || ! Schema::connection($connection)->hasTable('sidulur_citizens')) {
            return [];
        }

        if (! $user->can(SidulurPermissions::CITIZEN_VIEW)) {
            return [new ContactContext(
                source: $this->label(),
                emptyMessage: __('Butuh izin Data Warga untuk melihat data pemohon.'),
            )];
        }

        $citizen = Citizen::query()
            ->with('familyCard')
            ->where('phone_hash', BlindIndex::make($phone))
            ->first();

        if ($citizen === null) {
            return [$this->unregistered($phone, $user)];
        }

        return [$this->profile($citizen, $user, $phone)];
    }

    protected function profile(Citizen $citizen, $user, string $phone): ContactContext
    {
        $card = $citizen->familyCard;

        $address = collect([$card?->alamat, $card?->village_name, $card?->district_name])
            ->filter()
            ->implode(', ');

        return new ContactContext(
            source: $this->label(),
            name: $citizen->nama,
            caption: $citizen->is_active ? __('Warga aktif') : __('Warga nonaktif'),
            captionTone: $citizen->is_active ? 'teal' : 'gray',
            fields: array_filter([
                __('NIK') => $citizen->maskedNik(),
                __('Alamat') => $address,
            ]),
            history: $citizen->pendaftarans()
                ->with('service')
                ->orderByDesc('created_at')
                ->limit(3)
                ->get()
                ->map(fn (Pendaftaran $pendaftaran) => new ContactHistoryItem(
                    title: $pendaftaran->service?->nama ?? __('Pengajuan'),
                    meta: trim(($pendaftaran->ticket_number ?? '').' · '.($pendaftaran->created_at?->translatedFormat('d M Y') ?? ''), ' ·'),
                    status: str($pendaftaran->status)->title()->value(),
                    tone: $pendaftaran->status === Pendaftaran::STATUS_SELESAI ? 'teal' : 'amber',
                    url: route('sidulur.pendaftaran.show', $pendaftaran->getKey()),
                ))
                ->all(),
            panelView: $user->can(SidulurPermissions::PENDAFTARAN_CREATE)
                ? 'sidulur::partials.chat.service-panel'
                : null,
            panelData: ['phone' => $phone],
            historyUrl: route('sidulur.citizen.show', $citizen->getKey()),
        );
    }

    protected function unregistered(string $phone, $user): ContactContext
    {
        return new ContactContext(
            source: $this->label(),
            emptyMessage: __('Nomor ini belum terdaftar sebagai warga.'),
            fallbackAction: $user->can(SidulurPermissions::CITIZEN_CREATE)
                ? ['label' => __('Daftarkan warga'), 'url' => route('sidulur.citizen.form', ['phone' => $phone])]
                : null,
        );
    }
}
