{{--
| Blok tetap identitas & berkas warga. Bukan bagian Form Builder.
| Parameter: $citizen, $missing (kunci sumber belum lengkap),
|            $checkable (checkbox "sudah diperiksa"), $refreshable (tombol periksa ulang).
--}}
@php
    $missing = $missing ?? [];
    $checkable = $checkable ?? false;
    $refreshable = $refreshable ?? false;
    $preview = app(\Bale\Sidulur\Services\DocumentService::class);
    $ktp = $preview->preview($citizen->documents->firstWhere('kind', 'ktp'));
    $kk = $preview->preview($citizen->familyCard?->documents->firstWhere('kind', 'kk'));
    $lengkap = $missing === [];
@endphp

<div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="flex flex-wrap items-start justify-between gap-2">
        <div>
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Identitas & Berkas Warga') }}</h2>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Diambil dari Data Warga dan wajib untuk semua layanan.') }}</p>
        </div>
        <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-medium {{ $lengkap
            ? 'bg-teal-50 text-teal-700 border-teal-100 dark:bg-teal-900/20 dark:text-teal-300 dark:border-teal-800/40'
            : 'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800/40' }}">
            {{ $lengkap ? __('Lengkap') : __('Belum lengkap') }}
        </span>
    </div>

    <dl class="mt-3 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
        <div>
            <dt class="text-gray-500 dark:text-gray-400">{{ __('Nama') }}</dt>
            <dd class="font-medium text-gray-900 dark:text-white">{{ $citizen->nama ?: '—' }}</dd>
        </div>
        <div>
            <dt class="text-gray-500 dark:text-gray-400">{{ __('NIK') }}</dt>
            <dd class="font-mono text-gray-900 dark:text-white">{{ $citizen->maskedNik() ?: '—' }}</dd>
        </div>
        <div>
            <dt class="text-gray-500 dark:text-gray-400">{{ __('Nomor KK') }}</dt>
            <dd class="font-mono text-gray-900 dark:text-white">{{ $citizen->familyCard?->nomor_kk ?: '—' }}</dd>
        </div>
        <div>
            <dt class="text-gray-500 dark:text-gray-400">{{ __('Nomor HP') }}</dt>
            <dd class="text-gray-900 dark:text-white">{{ $citizen->phone ?: '—' }}</dd>
        </div>
        <div class="sm:col-span-2">
            <dt class="text-gray-500 dark:text-gray-400">{{ __('Alamat') }}</dt>
            <dd class="text-gray-900 dark:text-white">
                {{ $citizen->familyCard?->alamat ?: '—' }}{{ $citizen->familyCard?->village_name ? ', '.$citizen->familyCard->village_name : '' }}
            </dd>
        </div>
    </dl>

    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div>
            <p class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ __('Berkas KTP') }}</p>
            @if($ktp)
                @include('sidulur::partials.document-preview', ['document' => $ktp, 'label' => __('KTP warga')])
            @else
                <p class="mt-1 text-xs italic text-rose-600 dark:text-rose-400">{{ __('Belum diunggah di Data Warga.') }}</p>
            @endif
        </div>
        <div>
            <p class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ __('Berkas Kartu Keluarga') }}</p>
            @if($kk)
                @include('sidulur::partials.document-preview', ['document' => $kk, 'label' => __('Kartu Keluarga')])
            @else
                <p class="mt-1 text-xs italic text-rose-600 dark:text-rose-400">{{ __('Belum diunggah di Data Warga.') }}</p>
            @endif
        </div>
    </div>

    @if(! $lengkap)
        <p class="mt-3 text-xs text-amber-700 dark:text-amber-300">
            {{ __('Lengkapi :berkas di Data Warga. Setelah tersimpan, tekan Periksa ulang berkas.', ['berkas' => collect($missing)->map(fn ($key) => \Bale\Sidulur\Support\FormSchema::identityLabel($key))->implode(', ')]) }}
        </p>
    @endif

    <div class="mt-3 flex flex-wrap items-center gap-2">
        @can('sidulur.citizen.update')
            <x-core::secondary-button link href="{{ route('sidulur.citizen.form', $citizen->getKey()) }}" label="{{ __('Lengkapi di Data Warga') }}" />
        @else
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Minta petugas pendaftaran melengkapi Data Warga.') }}</p>
        @endcan

        @if($refreshable)
            <x-core::secondary-button type="button" wire:click="$refresh" label="{{ __('Periksa ulang berkas') }}" />
        @endif
    </div>

    @if($checkable)
        <div class="mt-3 border-t border-gray-100 pt-3 dark:border-gray-700">
            @if($lengkap)
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <x-core::checkbox wire:model="requirements.{{ \Bale\Sidulur\Support\FormSchema::IDENTITY_KEY }}" /> {{ __('Identitas dan berkas sudah diperiksa') }}
                </label>
                @error('requirements.'.\Bale\Sidulur\Support\FormSchema::IDENTITY_KEY)
                    <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror
            @else
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Centang pemeriksaan tersedia setelah berkas lengkap.') }}</p>
            @endif
        </div>
    @endif
</div>
