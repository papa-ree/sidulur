<div class="space-y-4">
    <x-core::page-header :title="$activeService->nama" :subtitle="__('Persyaratan, field respon bidang, dan field verifikasi lapangan.')">
        <x-slot name="action">
            <x-core::secondary-button link href="{{ route('sidulur.form.index') }}" label="{{ __('Pilih Layanan') }}" />
        </x-slot>
    </x-core::page-header>

    {{-- Header builder: nama formulir mengikuti input di tab Builder --}}
    <div class="flex items-center gap-4 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="shrink-0 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 p-3 shadow-lg shadow-indigo-500/20">
            <x-lucide-file-pen-line class="h-6 w-6 text-white" />
        </div>
        <div class="min-w-0">
            <p class="mb-0.5 text-[10px] font-medium uppercase tracking-wider text-indigo-600 dark:text-indigo-400">{{ __('Form Builder') }}</p>
            <h2 class="truncate text-lg font-bold text-gray-900 dark:text-white">{{ $nama ?: __('Formulir tanpa nama') }}</h2>
            <p class="truncate text-sm text-gray-500 dark:text-gray-400">
                {{ $activeService->bidang?->nama }} · {{ __('versi') }} {{ $version }}
            </p>
        </div>
    </div>

    {{-- Tab bar: pindah tab lewat request agar nilai deferred ikut terkirim --}}
    <div class="flex w-fit items-center gap-1 rounded-xl bg-gray-100 p-1 dark:bg-slate-800">
        <button type="button" wire:click="$set('showPreview', false)"
            @class([
                'inline-flex items-center gap-x-2 rounded-lg px-4 py-2 text-sm font-medium transition-all duration-200',
                'bg-white text-gray-900 shadow-sm dark:bg-slate-700 dark:text-white' => ! $showPreview,
                'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' => $showPreview,
            ])>
            <x-lucide-hammer class="h-4 w-4" />
            {{ __('Builder') }}
        </button>
        <button type="button" wire:click="$set('showPreview', true)"
            @class([
                'inline-flex items-center gap-x-2 rounded-lg px-4 py-2 text-sm font-medium transition-all duration-200',
                'bg-white text-gray-900 shadow-sm dark:bg-slate-700 dark:text-white' => $showPreview,
                'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' => ! $showPreview,
            ])>
            <x-lucide-eye class="h-4 w-4" />
            {{ __('Preview') }}
        </button>
    </div>

    @if(! $showPreview)
        @can('sidulur.form.update')
            <form wire:submit="save" class="space-y-4">
                {{-- Identitas formulir --}}
                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="mb-4 flex items-center gap-3">
                        <div class="rounded-lg bg-indigo-50 p-2 dark:bg-indigo-900/20">
                            <x-lucide-info class="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('Identitas Formulir') }}</h3>
                            <p class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-500">{{ __('Nama formulir yang tampil ke petugas') }}</p>
                        </div>
                    </div>
                    <x-core::input label="{{ __('Nama formulir') }}" wire:model="nama" />
                    @error('nama') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                </div>

                {{-- Persyaratan --}}
                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="rounded-lg bg-purple-50 p-2 dark:bg-purple-900/20">
                                <x-lucide-clipboard-list class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('Persyaratan') }}</h3>
                                <p class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-500">{{ __('Syarat tambahan; identitas & berkas warga otomatis') }}</p>
                            </div>
                        </div>
                        <button type="button" wire:click="addRequirement"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-purple-600 hover:underline dark:text-purple-400">
                            <x-lucide-plus class="h-3.5 w-3.5" /> {{ __('Tambah') }}
                        </button>
                    </div>

                    <div class="space-y-3">
                        @forelse($schema['persyaratan'] as $index => $row)
                            <div wire:key="syarat-{{ $index }}" class="rounded-xl border border-gray-100 p-4 dark:border-slate-800">
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                    <x-core::input label="{{ __('Kode') }}" wire:model="schema.persyaratan.{{ $index }}.kode" />
                                    <x-core::input label="{{ __('Nama') }}" wire:model="schema.persyaratan.{{ $index }}.nama" />
                                    <x-core::select-dropdown label="{{ __('Tipe isian') }}" model="schema.persyaratan.{{ $index }}.type" :items="collect($requirementTypes)->map(fn ($t) => ['value' => $t, 'label' => ucfirst($t)])->all()" valueField="value" labelField="label" placeholder="{{ __('Berkas') }}" />
                                </div>
                                @error("schema.persyaratan.{$index}.kode")
                                    <p class="mt-2 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                                @enderror
                                @error("schema.persyaratan.{$index}.nama")
                                    <p class="mt-2 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                                @enderror
                                <div class="mt-3 flex items-center justify-between">
                                    <label class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                                        <x-core::checkbox wire:model="schema.persyaratan.{{ $index }}.wajib" /> {{ __('Wajib') }}
                                    </label>
                                    <button type="button" wire:click="removeRequirement({{ $index }})"
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-rose-600 hover:underline dark:text-rose-400">
                                        <x-lucide-trash-2 class="h-3.5 w-3.5" /> {{ __('Hapus') }}
                                    </button>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs italic text-gray-400 dark:text-gray-500">{{ __('Belum ada persyaratan.') }}</p>
                        @endforelse
                    </div>
                </div>

                {{-- Field respon & verifikasi --}}
                @foreach(['fields' => __('Field Respon Bidang'), 'verifikasi' => __('Field Verifikasi Lapangan')] as $section => $title)
                    <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="rounded-lg p-2 {{ $section === 'fields' ? 'bg-amber-50 dark:bg-amber-900/20' : 'bg-emerald-50 dark:bg-emerald-900/20' }}">
                                    @if($section === 'fields')
                                        <x-lucide-list class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                                    @else
                                        <x-lucide-map-pin-check class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                                    @endif
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ $title }}</h3>
                                    <p class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-500">
                                        {{ $section === 'fields' ? __('Diisi petugas bidang saat menyelesaikan tiket') : __('Diisi petugas lapangan saat verifikasi') }}
                                    </p>
                                </div>
                            </div>
                            <button type="button" wire:click="addField('{{ $section }}')"
                                class="inline-flex items-center gap-1 text-xs font-semibold text-purple-600 hover:underline dark:text-purple-400">
                                <x-lucide-plus class="h-3.5 w-3.5" /> {{ __('Tambah') }}
                            </button>
                        </div>

                        <div class="space-y-3">
                            @forelse($schema[$section] as $index => $row)
                                <div wire:key="{{ $section }}-{{ $index }}" class="rounded-xl border border-gray-100 p-4 dark:border-slate-800">
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        <x-core::input label="{{ __('Key') }}" wire:model="schema.{{ $section }}.{{ $index }}.key" />
                                        <x-core::input label="{{ __('Label') }}" wire:model="schema.{{ $section }}.{{ $index }}.label" />
                                    </div>
                                    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        <x-core::select-dropdown label="{{ __('Tipe') }}" model="schema.{{ $section }}.{{ $index }}.type" :items="collect($types)->map(fn ($t) => ['value' => $t, 'label' => ucfirst($t)])->all()" valueField="value" labelField="label" />
                                        <div>
                                            <x-core::input label="{{ __('Opsi (pisahkan koma)') }}" wire:model="optionsText.{{ $section }}.{{ $index }}" placeholder="Opsi A, Opsi B" />
                                            <p class="mt-1 text-[10px] text-gray-400 dark:text-gray-500">{{ __('Hanya dipakai untuk tipe select.') }}</p>
                                        </div>
                                    </div>
                                    <div class="mt-3 flex items-center justify-between">
                                        <label class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                                            <x-core::checkbox wire:model="schema.{{ $section }}.{{ $index }}.required" /> {{ __('Wajib') }}
                                        </label>
                                        <button type="button" wire:click="removeField('{{ $section }}', {{ $index }})"
                                            class="inline-flex items-center gap-1 text-xs font-semibold text-rose-600 hover:underline dark:text-rose-400">
                                            <x-lucide-trash-2 class="h-3.5 w-3.5" /> {{ __('Hapus') }}
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs italic text-gray-400 dark:text-gray-500">{{ __('Belum ada field.') }}</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach

                {{-- Aksi --}}
                <div class="flex items-center justify-between gap-3 border-t border-gray-100 pt-4 dark:border-slate-800">
                    <button type="button" wire:click="resetForm"
                        wire:confirm="{{ __('Kosongkan seluruh persyaratan dan field? Perubahan baru berlaku setelah disimpan.') }}"
                        class="text-xs font-semibold text-rose-600 hover:underline dark:text-rose-400">
                        {{ __('Mulai dari kosong') }}
                    </button>
                    <x-core::button type="submit" label="{{ __('Simpan Formulir') }}">
                        <x-slot name="icon"><x-lucide-save class="h-4 w-4" /></x-slot>
                    </x-core::button>
                </div>
            </form>
        @else
            <div class="rounded-2xl border border-gray-100 bg-white p-6 text-sm text-gray-600 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-gray-300">
                {{ __('Anda hanya memiliki akses baca untuk formulir ini.') }}
            </div>
        @endcan
    @else
        {{-- Preview --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="mb-4 flex items-center gap-3">
                <div class="rounded-lg bg-indigo-50 p-2 dark:bg-indigo-900/20">
                    <x-lucide-eye class="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('Preview Formulir') }}</h3>
                    <p class="text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-500">{{ __('Tampilan field respon dan verifikasi') }}</p>
                </div>
            </div>

            <div class="space-y-4 rounded-xl border border-indigo-100 bg-gradient-to-br from-indigo-50/50 to-purple-50/50 p-6 dark:border-indigo-800/30 dark:from-indigo-900/10 dark:to-purple-900/10">
                @if(count($schema['fields']) || count($schema['verifikasi']))
                    <div class="space-y-4">
                        @include('sidulur::partials.dynamic-fields', ['fields' => $schema['fields'], 'modelPrefix' => 'previewData'])
                    </div>
                    @if(count($schema['verifikasi']))
                        <h4 class="mb-2 mt-5 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Verifikasi Lapangan') }}</h4>
                        <div class="space-y-4">
                            @include('sidulur::partials.dynamic-fields', ['fields' => $schema['verifikasi'], 'modelPrefix' => 'previewData'])
                        </div>
                    @endif
                @else
                    <div class="flex flex-col items-center justify-center py-8 text-center">
                        <span class="flex size-12 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                            <x-lucide-inbox class="h-6 w-6" />
                        </span>
                        <p class="mt-3 text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Belum ada field.') }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Tambahkan field di tab Builder lalu simpan.') }}</p>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
