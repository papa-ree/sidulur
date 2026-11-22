@php
    $status = $pelayanan->status;
    $tone = match ($status) {
        'selesai', 'verlap_selesai' => 'bg-teal-50 text-teal-700 border-teal-100 dark:bg-teal-900/20 dark:text-teal-300 dark:border-teal-800/40',
        'dijadwalkan' => 'bg-sky-50 text-sky-700 border-sky-100 dark:bg-sky-900/20 dark:text-sky-300 dark:border-sky-800/40',
        default => 'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800/40',
    };
    $canUpdate = auth()->user()->can('sidulur.pelayanan.update');
    $canRespond = $pelayanan->bolehRespon() && $canUpdate;
    $canUpload = $pelayanan->bolehUnggahHasil() && $canUpdate;
    $butuhVerlap = (bool) $pelayanan->service?->butuh_verifikasi;
    $butuhJadwal = $status === 'baru' && $butuhVerlap && $canUpdate;
    $bisaVerifikasi = $canUpdate && ($canRespond || $canUpload || $butuhJadwal);

    $langkah = match (true) {
        $status === 'dijadwalkan' => __('Menunggu hasil verifikasi lapangan.'),
        $canRespond => __('Lengkapi respon bidang, lalu selesaikan pengajuan.'),
        $butuhJadwal => __('Tentukan jadwal verifikasi lapangan terlebih dahulu.'),
        $status === 'selesai' => __('Pengajuan selesai. Dokumen hasil layanan masih dapat diganti bila perlu.'),
        default => null,
    };

    // Sisa syarat wajib yang belum dicentang petugas, dipakai sebagai penanda di tab Verifikasi.
    $wajib = [];
    foreach ($syarat as $row) {
        if ($row['required'] ?? false) {
            $wajib[] = $row['key'];
        }
    }

    // Identitas & berkas warga hanya bisa dicentang saat berkasnya lengkap.
    if ($identityMissing === []) {
        $wajib[] = \Bale\Sidulur\Support\FormSchema::IDENTITY_KEY;
    }

    $sisaPeriksa = count($wajib) - count(array_intersect($wajib, array_keys(array_filter($requirements))));
@endphp

<div class="space-y-4">
    <x-core::page-header :title="$pelayanan->pendaftaran?->ticket_number" :subtitle="__('Pelayanan bidang: ').$pelayanan->service?->nama">
        <x-slot name="action">
            <x-core::secondary-button link href="{{ route('sidulur.pelayanan.index') }}" label="{{ __('Kembali') }}" />
        </x-slot>
    </x-core::page-header>

    {{-- Status ringkas: satu tempat untuk tahu posisi pengajuan dan langkah berikutnya. --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Status pengajuan') }}</p>
                <div class="mt-1.5 flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-medium {{ $tone }}">{{ str($status)->replace('_', ' ')->title() }}</span>
                    @if($butuhVerlap)
                        <span class="inline-flex items-center rounded-md border border-gray-200 bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ __('Butuh verifikasi lapangan') }}</span>
                    @endif
                </div>
            </div>

            <dl class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
                <div class="flex items-baseline gap-1.5">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Bidang') }}</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $pelayanan->bidang?->nama ?: '—' }}</dd>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Jadwal verlap') }}</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $pelayanan->scheduled_at?->format('d M Y') ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        @if($langkah || $pelayanan->scheduled_note)
            <div class="mt-3 space-y-1 border-t border-gray-100 pt-3 dark:border-gray-700">
                @if($langkah)
                    <p class="text-xs text-gray-600 dark:text-gray-300">{{ $langkah }}</p>
                @endif
                @if($pelayanan->scheduled_note)
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Catatan jadwal:') }} {{ $pelayanan->scheduled_note }}</p>
                @endif
            </div>
        @endif
    </div>

    {{-- Dua bagian terpisah: data bacaan (Ringkasan) dan tindakan petugas (Verifikasi). --}}
    <div x-data="{ aktif: @js($bisaVerifikasi && ($canRespond || $butuhJadwal) ? 'verifikasi' : 'ringkasan') }" class="space-y-4">
        @if($bisaVerifikasi)
            <div role="group" aria-label="{{ __('Bagian halaman') }}"
                 class="flex w-full gap-1 rounded-xl border border-gray-200 bg-gray-100 p-1 dark:border-gray-700 dark:bg-gray-800 sm:inline-flex sm:w-auto">
                <button type="button" x-on:click="aktif = 'ringkasan'" x-bind:aria-pressed="aktif === 'ringkasan'"
                        x-bind:class="aktif === 'ringkasan' ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-700 dark:text-white' : 'text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200'"
                        class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-lg px-4 text-sm font-medium transition-colors sm:flex-none">
                    <x-lucide-clipboard-list class="size-4" aria-hidden="true" />
                    {{ __('Ringkasan') }}
                </button>

                <button type="button" x-on:click="aktif = 'verifikasi'" x-bind:aria-pressed="aktif === 'verifikasi'"
                        x-bind:class="aktif === 'verifikasi' ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-700 dark:text-white' : 'text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200'"
                        class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-lg px-4 text-sm font-medium transition-colors sm:flex-none">
                    <x-lucide-clipboard-check class="size-4" aria-hidden="true" />
                    {{ __('Verifikasi') }}
                    @if($canRespond && $sisaPeriksa > 0)
                        <span class="rounded-full bg-amber-100 px-1.5 py-0.5 text-[11px] font-semibold text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">{{ $sisaPeriksa }}</span>
                    @endif
                </button>
            </div>
        @endif

        <div @if($bisaVerifikasi) x-show="aktif === 'ringkasan'" x-cloak @endif class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                {{-- Tanpa izin periksa, identitas hanya bisa dibaca di sini. --}}
                @if(! $canRespond && $pelayanan->pendaftaran?->citizen)
                    @include('sidulur::partials.identity', [
                        'citizen' => $pelayanan->pendaftaran->citizen,
                        'missing' => $identityMissing,
                        'checkable' => false,
                        'refreshable' => false,
                    ])
                @endif

                {{-- Saat petugas bisa memeriksa, berkas tampil di tab Verifikasi (hindari render ganda). --}}
                @if(count($syarat) && ! $canRespond)
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Persyaratan') }}</h2>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Berkas dari pemohon. Periksa kelengkapannya sebelum menyelesaikan.') }}</p>
                        <div class="mt-3">
                            @include('sidulur::partials.requirements', [
                                'requirements' => $syarat,
                                'values' => $requirementValues,
                                'documents' => $requirementDocuments,
                                'inspect' => false,
                            ])
                        </div>
                    </div>
                @endif

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">{{ __('Timeline Tiket') }}</h2>
                    @include('sidulur::partials.timeline', ['timelines' => $pelayanan->pendaftaran?->timelines ?? collect()])
                </div>
            </div>

            <div class="space-y-4">
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Hasil Verifikasi Lapangan') }}</h2>
                    @if($pelayanan->verlap_data)
                        <dl class="mt-3 space-y-2 text-sm">
                            @foreach($pelayanan->verlap_snapshot ?? [] as $field)
                                <div>
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">{{ $field['label'] }}</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ data_get($pelayanan->verlap_data, $field['key']) ?: '—' }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @else
                        <div class="flex flex-col items-center justify-center py-6 text-center">
                            <span class="flex size-10 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                <x-lucide-map-pin class="size-5" aria-hidden="true" />
                            </span>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ __('Belum ada hasil verifikasi lapangan.') }}</p>
                        </div>
                    @endif
                </div>

                {{-- Tanpa akses ubah, dokumen hasil hanya bisa dibaca di sini. --}}
                @unless($bisaVerifikasi)
                    @include('sidulur::partials.hasil', ['pelayanan' => $pelayanan, 'canUpload' => false])
                @endunless
            </div>
        </div>

        @if($bisaVerifikasi)
            <div x-show="aktif === 'verifikasi'" x-cloak class="space-y-4">
                @if($butuhJadwal)
                    <form wire:submit="schedule" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Jadwalkan Verifikasi Lapangan') }}</h2>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Verifikasi lapangan dilakukan setelah jadwal ditentukan.') }}</p>
                        <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <x-core::date-picker mode="date" model="scheduled_at" label="{{ __('Tanggal kunjungan') }}" minDate="{{ now()->toDateString() }}" />
                            <x-core::input label="{{ __('Catatan') }}" wire:model="scheduled_note" />
                        </div>
                        <div class="mt-4">
                            <x-core::button type="submit" :label="__('Simpan Jadwal')" />
                        </div>
                    </form>
                @endif

                @if($canRespond)
                    @if($pelayanan->pendaftaran?->citizen)
                        @include('sidulur::partials.identity', [
                            'citizen' => $pelayanan->pendaftaran->citizen,
                            'missing' => $identityMissing,
                            'checkable' => true,
                            'refreshable' => true,
                        ])
                        @error('identitas')
                            <p class="rounded-lg bg-rose-50 px-3 py-2 text-xs text-rose-700 dark:bg-rose-900/20 dark:text-rose-300">{{ $message }}</p>
                        @enderror
                    @endif

                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Persyaratan Tambahan') }}</h2>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Berkas dari pemohon. Centang syarat wajib yang sudah diperiksa sebelum menyelesaikan.') }}</p>
                            </div>
                            @if(count($wajib))
                                <span class="inline-flex shrink-0 items-center rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    {{ count($wajib) - $sisaPeriksa }}/{{ count($wajib) }} {{ __('diperiksa') }}
                                </span>
                            @endif
                        </div>
                        <div class="mt-3">
                            @include('sidulur::partials.requirements', [
                                'requirements' => $syarat,
                                'values' => $requirementValues,
                                'documents' => $requirementDocuments,
                                'inspect' => true,
                            ])
                        </div>
                    </div>
                @endif

                @if($canRespond)
                    <form wire:submit="finish" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Respon Bidang') }}</h2>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Isi hasil pemeriksaan. Menyimpan respon menutup pengajuan ini.') }}</p>
                        <div class="mt-3 space-y-4">
                            @include('sidulur::partials.dynamic-fields', ['fields' => $pelayanan->form_snapshot['fields'] ?? [], 'modelPrefix' => 'formData'])
                            <x-core::textarea label="{{ __('Catatan penyelesaian') }}" wire:model="note" rows="2" />
                        </div>
                        <div class="mt-4">
                            <x-core::button type="submit" variant="success" :label="__('Simpan & Selesaikan')" />
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ __('Bila layanan ini menghasilkan dokumen, unggah dulu di kartu Dokumen Hasil Layanan.') }}</p>
                        </div>
                    </form>
                @endif

                @include('sidulur::partials.hasil', ['pelayanan' => $pelayanan, 'canUpload' => $canUpload])
            </div>
        @endif
    </div>
</div>
