@php
    $label = collect($identityMissing)
        ->map(fn (string $key) => \Bale\Sidulur\Support\FormSchema::identityLabel($key))
        ->implode(', ');
@endphp

<div class="space-y-3"
     x-data="{ open: false }"
     x-on:sidulur-service-closed.window="open = false">
    @if($ticket)
        <div class="rounded-lg border border-teal-100 bg-teal-50 px-3 py-2.5 dark:border-teal-800/40 dark:bg-teal-900/20">
            <p class="text-xs font-medium text-teal-700 dark:text-teal-300">{{ __('Pendaftaran tersimpan') }}</p>
            <p class="mt-0.5 font-mono text-sm font-semibold text-teal-900 dark:text-teal-100">{{ $ticket }}</p>

            @if($ticketUrl)
                <a href="{{ $ticketUrl }}"
                   wire:navigate.hover
                   class="mt-1 inline-flex items-center gap-0.5 text-xs font-medium text-teal-700 hover:underline dark:text-teal-300">
                    {{ __('Buka pengajuan') }}
                    <x-lucide-chevron-right class="size-3" aria-hidden="true" />
                </a>
            @endif
        </div>
    @endif

    <x-core::secondary-button
        type="button"
        x-show="! open"
        x-on:click="open = true"
        class="w-full!"
        :label="__('Tambah layanan')" />

    <x-core::secondary-button
        type="button"
        x-cloak
        x-show="open"
        x-on:click="open = false"
        class="w-full!"
        :label="__('Tutup form layanan')" />

    {{-- Daftar layanan sudah dirender sejak awal; buka/tutup dikelola Alpine
         supaya tidak perlu round trip. --}}
    <div x-show="open" x-cloak>
        <form wire:submit="forward" class="space-y-3">
            <div wire:loading wire:target="selectService" class="space-y-2 rounded-lg border border-gray-200 p-2.5 dark:border-gray-700">
                <div class="h-4 w-2/3 animate-pulse rounded bg-gray-100 dark:bg-gray-800"></div>
                <div class="h-3 w-1/3 animate-pulse rounded bg-gray-100 dark:bg-gray-800"></div>
                @for($i = 0; $i < 3; $i++)
                    <div class="h-9 animate-pulse rounded-lg bg-gray-100 dark:bg-gray-800"></div>
                @endfor
                <p class="sr-only">{{ __('Memuat formulir layanan…') }}</p>
            </div>

            <div wire:loading wire:target="changeService" class="space-y-1.5 rounded-lg border border-gray-200 p-2.5 dark:border-gray-700">
                @for($i = 0; $i < 4; $i++)
                    <div class="h-10 animate-pulse rounded-lg bg-gray-100 dark:bg-gray-800"></div>
                @endfor
                <p class="sr-only">{{ __('Memuat daftar layanan…') }}</p>
            </div>

            <div wire:loading.remove wire:target="selectService,changeService">
            @if(! $service)
                <div class="rounded-lg border border-gray-200 p-2.5 dark:border-gray-700">
                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('Pilih layanan') }}</p>

                    <div class="mt-2 max-h-64 space-y-1.5 overflow-y-auto overscroll-contain pr-0.5 scrollbar-thin scrollbar-thumb-gray-400 dark:scrollbar-thumb-gray-600 scrollbar-track-gray-200 dark:scrollbar-track-gray-800 scrollbar-thumb-rounded-full">
                        @forelse($services as $item)
                            <button type="button"
                                    wire:key="sidulur-chat-service-{{ $item->getKey() }}"
                                    wire:click="selectService('{{ $item->getKey() }}')"
                                    class="flex w-full items-start gap-2 rounded-lg border border-gray-200 px-2.5 py-2 text-left transition-colors hover:border-purple-300 hover:bg-purple-50/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 dark:border-gray-700 dark:hover:border-purple-700 dark:hover:bg-purple-950/20">
                                <span class="mt-0.5 shrink-0">
                                    {!! \Bale\Sidulur\Support\Lucide::render($item->icon, 'size-4 text-purple-600 dark:text-purple-400') !!}
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-medium leading-snug break-words text-gray-900 dark:text-gray-100">{{ $item->nama }}</span>
                                    @if($item->bidang?->nama)
                                        <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">{{ $item->bidang->nama }}</span>
                                    @endif
                                </span>
                            </button>
                        @empty
                            <p class="py-2 text-center text-xs text-gray-500 dark:text-gray-400">{{ __('Belum ada layanan aktif.') }}</p>
                        @endforelse
                    </div>
                </div>
            @else
                <div class="rounded-lg border border-gray-200 p-2.5 dark:border-gray-700">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold leading-snug break-words text-gray-900 dark:text-white">{{ $service->nama }}</p>
                            @if($service->bidang?->nama)
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $service->bidang->nama }}</p>
                            @endif
                        </div>

                        <button type="button"
                                wire:click="changeService"
                                class="shrink-0 rounded text-xs font-semibold text-purple-600 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 dark:text-purple-400">
                            {{ __('Ganti') }}
                        </button>
                    </div>

                    @if(count($requirements))
                        <div class="mt-3">
                            <p class="mb-2 text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('Persyaratan tambahan') }}</p>

                            @include('sidulur::partials.requirements', [
                                'requirements' => $requirements,
                                'dataModel' => 'requirementData',
                                'filesModel' => 'requirementFiles',
                                'mediaPicker' => true,
                                'mediaMessages' => $mediaMessages,
                                'mediaModel' => 'requirementMedia',
                                'mediaSelected' => $requirementMedia,
                            ])
                        </div>
                    @endif

                    <div class="mt-3 space-y-3">
                        @include('sidulur::partials.dynamic-fields', [
                            'fields' => $service->form?->schema['fields'] ?? [],
                            'modelPrefix' => 'formData',
                        ])
                    </div>

                    @if($identityMissing !== [])
                        <div class="mt-3 rounded-lg bg-amber-50 px-2.5 py-2 text-xs text-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
                            <p>{{ __('Identitas/berkas warga belum lengkap: :berkas.', ['berkas' => $label]) }}</p>

                            @can('sidulur.citizen.update')
                                <a href="{{ route('sidulur.citizen.form', $citizenId) }}"
                                   wire:navigate.hover
                                   class="mt-1 inline-block font-semibold underline">
                                    {{ __('Lengkapi di Data Warga') }}
                                </a>
                            @endcan
                        </div>
                    @endif
                </div>

                <div class="space-y-2">
                    @if($service->selesai_di_pendaftaran)
                        <x-core::button type="button" wire:click="finishDesk" variant="success" class="w-full!" wire:loading.attr="disabled" wire:target="finishDesk,forward" :label="__('Selesaikan di Pendaftaran')" />
                    @endif

                    <x-core::button type="submit" class="w-full!" wire:loading.attr="disabled" wire:target="finishDesk,forward" :label="__('Kirim ke Bidang')" />

                    <p wire:loading wire:target="finishDesk,forward" class="text-center text-xs text-gray-500 dark:text-gray-400">
                        {{ __('Menyimpan pengajuan…') }}
                    </p>
                </div>

                @error('identitas')
                    <p class="rounded-lg bg-rose-50 px-2.5 py-2 text-xs text-rose-700 dark:bg-rose-900/20 dark:text-rose-300">{{ $message }}</p>
                @enderror
            @endif
            </div>
        </form>
    </div>
</div>
