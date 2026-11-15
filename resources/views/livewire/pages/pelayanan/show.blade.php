@php
    $status = $pelayanan->status;
    $tone = match ($status) {
        'selesai', 'verlap_selesai' => 'bg-teal-50 text-teal-700 border-teal-100 dark:bg-teal-900/20 dark:text-teal-300 dark:border-teal-800/40',
        'dijadwalkan' => 'bg-sky-50 text-sky-700 border-sky-100 dark:bg-sky-900/20 dark:text-sky-300 dark:border-sky-800/40',
        default => 'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800/40',
    };
    $canRespond = $status === 'verlap_selesai' || ($status === 'baru' && ! $pelayanan->service?->butuh_verifikasi);
@endphp

<div class="space-y-4">
    <x-core::page-header :title="$pelayanan->pendaftaran?->ticket_number" :subtitle="__('Pelayanan bidang: ').$pelayanan->service?->nama">
        <x-slot name="action">
            <x-core::secondary-button link href="{{ route('sidulur.pelayanan.index') }}" label="{{ __('Kembali') }}" />
        </x-slot>
    </x-core::page-header>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Data Pemohon') }}</h2>
                    <span class="inline-flex rounded-md border px-2 py-0.5 text-xs font-medium {{ $tone }}">{{ str($status)->replace('_', ' ')->title() }}</span>
                </div>
                <dl class="mt-3 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Nama') }}</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $pelayanan->pendaftaran?->citizen?->nama }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('NIK') }}</dt><dd class="font-mono text-gray-900 dark:text-white">{{ $pelayanan->pendaftaran?->citizen?->maskedNik() }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Bidang') }}</dt><dd class="text-gray-900 dark:text-white">{{ $pelayanan->bidang?->nama }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Jadwal verlap') }}</dt><dd class="text-gray-900 dark:text-white">{{ $pelayanan->scheduled_at?->format('d M Y') ?? '—' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-gray-500 dark:text-gray-400">{{ __('Alamat') }}</dt><dd class="text-gray-900 dark:text-white">{{ $pelayanan->pendaftaran?->citizen?->familyCard?->alamat }}, {{ $pelayanan->pendaftaran?->citizen?->familyCard?->village_name }}</dd></div>
                </dl>
                @if($pelayanan->scheduled_note)
                    <p class="mt-3 rounded-lg bg-sky-50 px-3 py-2 text-xs text-sky-700 dark:bg-sky-900/20 dark:text-sky-300">{{ $pelayanan->scheduled_note }}</p>
                @endif
            </div>

            @if($status === 'baru' && $pelayanan->service?->butuh_verifikasi)
                <form wire:submit="schedule" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Jadwalkan Verifikasi Lapangan') }}</h2>
                    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <x-core::input type="date" label="{{ __('Tanggal kunjungan') }}" wire:model="scheduled_at" />
                        <x-core::input label="{{ __('Catatan') }}" wire:model="scheduled_note" />
                    </div>
                    <div class="mt-3">
                        <x-core::button type="submit" label="{{ __('Simpan Jadwal') }}" />
                    </div>
                </form>
            @endif

            @if($canRespond)
                <form wire:submit="finish" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Respon Bidang') }}</h2>
                    <div class="mt-3 space-y-4">
                        @include('sidulur::partials.dynamic-fields', ['fields' => $pelayanan->form_snapshot['fields'] ?? [], 'modelPrefix' => 'formData'])
                        <x-core::textarea label="{{ __('Catatan penyelesaian') }}" wire:model="note" rows="2" />
                    </div>
                    <div class="mt-4">
                        <x-core::button type="submit" variant="success" label="{{ __('Simpan & Selesaikan') }}" />
                    </div>
                </form>
            @endif

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">{{ __('Timeline Tiket') }}</h2>
                @include('sidulur::partials.timeline', ['timelines' => $pelayanan->pendaftaran?->timelines ?? collect()])
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Hasil Verlap') }}</h2>
                @if($pelayanan->verlap_data)
                    <dl class="mt-2 space-y-2 text-sm">
                        @foreach($pelayanan->verlap_snapshot ?? [] as $field)
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400">{{ $field['label'] }}</dt>
                                <dd class="text-gray-900 dark:text-white">{{ data_get($pelayanan->verlap_data, $field['key']) ?: '—' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @else
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Belum ada hasil verifikasi lapangan.') }}</p>
                @endif
            </div>

            <form wire:submit="uploadHasil" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Dokumen Hasil') }}</h2>
                <input type="file" wire:model="hasil" accept=".jpg,.jpeg,.png,.pdf" class="mt-3 block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                @error('hasil') <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                <div class="mt-3">
                    <x-core::button type="submit" label="{{ __('Unggah') }}" />
                </div>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach($pelayanan->pendaftaran?->documents?->where('kind', 'hasil') ?? [] as $document)
                        <li class="flex items-center justify-between rounded-lg border border-gray-100 px-3 py-2 dark:border-gray-700">
                            <span class="truncate text-xs text-gray-600 dark:text-gray-300">{{ $document->original_name }}</span>
                            <a href="{{ route('sidulur.media', $document->getKey()) }}" class="text-xs font-medium text-purple-600 hover:underline dark:text-purple-400">{{ __('Unduh') }}</a>
                        </li>
                    @endforeach
                </ul>
            </form>
        </div>
    </div>
</div>
