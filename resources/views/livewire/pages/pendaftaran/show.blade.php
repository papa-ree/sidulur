@php
    $tone = fn (string $status) => match ($status) {
        'selesai', 'verlap_selesai' => 'bg-teal-50 text-teal-700 border-teal-100 dark:bg-teal-900/20 dark:text-teal-300 dark:border-teal-800/40',
        'dijadwalkan' => 'bg-sky-50 text-sky-700 border-sky-100 dark:bg-sky-900/20 dark:text-sky-300 dark:border-sky-800/40',
        default => 'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800/40',
    };
@endphp

<div class="space-y-4">
    <x-core::page-header :title="$pendaftaran->ticket_number" :subtitle="__('Detail pengajuan dan timeline tiket.')">
        <x-slot name="action">
            <x-core::secondary-button link href="{{ route('sidulur.pendaftaran.index') }}" label="{{ __('Kembali') }}" />
        </x-slot>
    </x-core::page-header>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Ringkasan') }}</h2>
                    <span class="inline-flex rounded-md border px-2 py-0.5 text-xs font-medium {{ $tone($pendaftaran->status) }}">{{ str($pendaftaran->status)->title() }}</span>
                </div>
                <dl class="mt-3 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Warga') }}</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $pendaftaran->citizen?->nama }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('NIK') }}</dt><dd class="font-mono text-gray-900 dark:text-white">{{ $pendaftaran->citizen?->maskedNik() }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Layanan') }}</dt><dd class="text-gray-900 dark:text-white">{{ $pendaftaran->service?->nama }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Bidang') }}</dt><dd class="text-gray-900 dark:text-white">{{ $pendaftaran->service?->bidang?->nama }}</dd></div>
                    @if($pendaftaran->keperluan)
                        <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Keperluan') }}</dt><dd class="text-gray-900 dark:text-white">{{ $pendaftaran->keperluan }}</dd></div>
                    @endif
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Berlaku sampai') }}</dt><dd class="text-gray-900 dark:text-white">{{ $pendaftaran->expires_at?->format('d M Y') ?? '—' }}</dd></div>
                </dl>

                @if($pendaftaran->form_snapshot['fields'] ?? [])
                    <h3 class="mt-5 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Data Formulir') }}</h3>
                    <dl class="mt-2 grid grid-cols-1 gap-2 text-sm sm:grid-cols-2">
                        @foreach($pendaftaran->form_snapshot['fields'] as $field)
                            <div class="rounded-lg border border-gray-100 px-3 py-2 dark:border-gray-700">
                                <dt class="text-xs text-gray-500 dark:text-gray-400">{{ $field['label'] }}</dt>
                                <dd class="text-gray-900 dark:text-white">{{ data_get($pendaftaran->form_data ?? [], $field['key']) ?: '—' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif

                @if($identityMissing)
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800/40 dark:bg-amber-900/20">
                        <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">{{ __('Identitas & berkas warga belum lengkap') }}</p>
                        <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">
                            {{ __('Lengkapi :berkas di Data Warga.', ['berkas' => collect($identityMissing)->map(fn ($key) => \Bale\Sidulur\Support\FormSchema::identityLabel($key))->implode(', ')]) }}
                        </p>
                        @can('sidulur.citizen.update')
                            <x-core::secondary-button link href="{{ route('sidulur.citizen.form', $pendaftaran->citizen->getKey()) }}" label="{{ __('Buka Data Warga') }}" class="mt-3" />
                        @endcan
                    </div>
                @endif

                @if(count($requirements))
                    <h3 class="mt-5 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Persyaratan Tambahan') }}</h3>
                    <div class="mt-2">
                        @include('sidulur::partials.requirements', [
                            'requirements' => $requirements,
                            'values' => $requirementValues,
                            'documents' => $pendaftaran->documents,
                        ])
                    </div>
                @endif
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">{{ __('Timeline') }}</h2>
                @include('sidulur::partials.timeline', ['timelines' => $pendaftaran->timelines])
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Pelayanan Bidang') }}</h2>
                @if($pendaftaran->pelayananBidang)
                    <div class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">{{ __('Status') }}</span><span class="inline-flex rounded-md border px-2 py-0.5 text-xs font-medium {{ $tone($pendaftaran->pelayananBidang->status) }}">{{ str($pendaftaran->pelayananBidang->status)->replace('_', ' ')->title() }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">{{ __('Jadwal verlap') }}</span><span class="text-gray-900 dark:text-white">{{ $pendaftaran->pelayananBidang->scheduled_at?->format('d M Y') ?? '—' }}</span></div>
                        <div class="pt-2">
                            @can('sidulur.pelayanan.view')
                                <a href="{{ route('sidulur.pelayanan.show', $pendaftaran->pelayananBidang->getKey()) }}" wire:navigate class="text-xs font-semibold text-purple-600 hover:underline dark:text-purple-400">{{ __('Buka pelayanan bidang') }}</a>
                            @endcan
                        </div>
                    </div>
                @else
                    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">{{ __('Belum diteruskan ke bidang.') }}</p>
                @endif
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Dokumen') }}</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    @forelse($pendaftaran->documents->whereIn('kind', \Bale\Sidulur\Models\Document::PUBLIC_KINDS) as $document)
                        <li class="flex items-center justify-between rounded-lg border border-gray-100 px-3 py-2 dark:border-gray-700">
                            <span class="uppercase text-xs font-semibold text-gray-600 dark:text-gray-300">{{ $document->kind }}</span>
                            <a href="{{ route('sidulur.media', $document->getKey()) }}" class="text-xs font-medium text-purple-600 hover:underline dark:text-purple-400">{{ __('Unduh') }}</a>
                        </li>
                    @empty
                        <li class="text-gray-500 dark:text-gray-400">{{ __('Belum ada dokumen hasil.') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
