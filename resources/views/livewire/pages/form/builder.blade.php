<div class="space-y-4">
    <x-core::page-header :title="$activeService->nama" :subtitle="__('Persyaratan, field respon bidang, dan field verifikasi lapangan.')">
        <x-slot name="action">
            <div class="flex gap-2">
                <x-core::secondary-button link href="{{ route('sidulur.form.index') }}" label="{{ __('Pilih Layanan') }}" />
                <x-core::secondary-button type="button" wire:click="$toggle('showPreview')" label="{{ __('Preview') }}" />
            </div>
        </x-slot>
    </x-core::page-header>

    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $activeService->bidang?->nama }} · {{ __('versi') }} {{ $version }}</p>

    <div class="grid grid-cols-1 gap-4 {{ $showPreview ? 'xl:grid-cols-2' : '' }}">
        <form wire:submit="save" class="space-y-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-5">
                <x-core::input label="{{ __('Nama formulir') }}" wire:model="nama" />
                @error('nama') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                <div class="mt-4 flex justify-end">
                    <x-core::button type="submit" label="{{ __('Simpan Formulir') }}" />
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Persyaratan') }}</h3>
                    <button type="button" wire:click="addRequirement" class="text-xs font-semibold text-purple-600 hover:underline dark:text-purple-400">+ {{ __('Tambah') }}</button>
                </div>
                <div class="mt-3 space-y-3">
                    @foreach($schema['persyaratan'] as $index => $row)
                        <div wire:key="syarat-{{ $index }}" class="rounded-lg border border-gray-100 p-3 dark:border-gray-700">
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                                <x-core::input label="{{ __('Kode') }}" wire:model="schema.persyaratan.{{ $index }}.kode" />
                                <x-core::input label="{{ __('Nama') }}" wire:model="schema.persyaratan.{{ $index }}.nama" />
                                <x-core::select-dropdown label="{{ __('Sumber master') }}" model="schema.persyaratan.{{ $index }}.sumber" :items="collect($sources)->map(fn ($s) => ['value' => $s, 'label' => strtoupper($s)])->all()" valueField="value" labelField="label" placeholder="{{ __('Manual') }}" />
                            </div>
                            <div class="mt-2 flex items-center justify-between">
                                <label class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                                    <x-core::checkbox wire:model="schema.persyaratan.{{ $index }}.wajib" /> {{ __('Wajib') }}
                                </label>
                                <button type="button" wire:click="removeRequirement({{ $index }})" class="text-xs font-semibold text-rose-600 hover:underline dark:text-rose-400">{{ __('Hapus') }}</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            @foreach(['fields' => __('Field Respon Bidang'), 'verifikasi' => __('Field Verifikasi Lapangan')] as $section => $title)
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-5">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $title }}</h3>
                        <button type="button" wire:click="addField('{{ $section }}')" class="text-xs font-semibold text-purple-600 hover:underline dark:text-purple-400">+ {{ __('Tambah') }}</button>
                    </div>
                    <div class="mt-3 space-y-3">
                        @foreach($schema[$section] as $index => $row)
                            <div wire:key="{{ $section }}-{{ $index }}" class="rounded-lg border border-gray-100 p-3 dark:border-gray-700">
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    <x-core::input label="{{ __('Key') }}" wire:model="schema.{{ $section }}.{{ $index }}.key" />
                                    <x-core::input label="{{ __('Label') }}" wire:model="schema.{{ $section }}.{{ $index }}.label" />
                                </div>
                                <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    <x-core::select-dropdown label="{{ __('Tipe') }}" model="schema.{{ $section }}.{{ $index }}.type" :items="collect($types)->map(fn ($t) => ['value' => $t, 'label' => $t])->all()" valueField="value" labelField="label" />
                                    <x-core::input label="{{ __('Opsi (pisahkan koma)') }}" value="{{ implode(',', $row['options'] ?? []) }}" wire:change="updateOptions('{{ $section }}', {{ $index }}, $event.target.value)" />
                                </div>
                                <div class="mt-2 flex items-center justify-between">
                                    <label class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                                        <x-core::checkbox wire:model="schema.{{ $section }}.{{ $index }}.required" /> {{ __('Wajib') }}
                                    </label>
                                    <button type="button" wire:click="removeField('{{ $section }}', {{ $index }})" class="text-xs font-semibold text-rose-600 hover:underline dark:text-rose-400">{{ __('Hapus') }}</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="flex justify-end">
                <x-core::button type="submit" label="{{ __('Simpan Formulir') }}" />
            </div>
        </form>

        @if($showPreview)
            <div class="space-y-4">
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-5">
                    <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">{{ __('Preview Formulir') }}</h3>
                    <div class="space-y-4">
                        @include('sidulur::partials.dynamic-fields', ['fields' => $schema['fields'], 'modelPrefix' => 'previewData'])
                    </div>
                    @if(count($schema['verifikasi']))
                        <h4 class="mt-5 mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Verifikasi Lapangan') }}</h4>
                        <div class="space-y-4">
                            @include('sidulur::partials.dynamic-fields', ['fields' => $schema['verifikasi'], 'modelPrefix' => 'previewData'])
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
