<div class="space-y-4">
    <x-core::page-header :title="__('Pendaftaran Baru')" :subtitle="__('Cari warga, pilih layanan, lalu tentukan penyelesaian.')" />

    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
        @foreach([1 => __('Warga'), 2 => __('Layanan'), 3 => __('Formulir')] as $number => $label)
            <span class="flex items-center gap-2 rounded-full px-3 py-1.5 {{ $step === $number ? 'bg-purple-600 text-white' : ($step > $number ? 'bg-teal-50 text-teal-700 dark:bg-teal-900/20 dark:text-teal-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400') }}">
                <span class="flex size-5 items-center justify-center rounded-full border border-current text-[10px]">{{ $number }}</span>
                {{ $label }}
            </span>
        @endforeach
    </div>

    @if($step === 1)
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-5">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Cari Warga') }}</h2>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Masukkan NIK, nomor KK, atau nomor HP.') }}</p>
                    <form wire:submit="search" class="mt-3 flex flex-col gap-2 sm:flex-row">
                        <x-core::input wire:model="identity" placeholder="{{ __('NIK / KK / HP') }}" class="flex-1" />
                        <x-core::button type="submit" label="{{ __('Cari') }}" />
                    </form>
                    @error('identity') <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror

                    @if($results)
                        <ul class="mt-4 divide-y divide-gray-100 rounded-lg border border-gray-200 dark:divide-gray-700 dark:border-gray-700">
                            @foreach($results as $result)
                                <li class="flex items-center justify-between gap-3 px-4 py-3">
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $result['nama'] }}</div>
                                        <div class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $result['nik'] }}</div>
                                    </div>
                                    <button type="button" wire:click="selectCitizen('{{ $result['id'] }}')" class="text-xs font-semibold text-purple-600 hover:underline dark:text-purple-400">{{ __('Pilih') }}</button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                @if($showNewCitizen)
                    <form wire:submit="createCitizen" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-5">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Warga Baru') }}</h2>
                        <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <x-core::input label="{{ __('Nama lengkap') }}" wire:model="nama" />
                            <x-core::input label="{{ __('NIK') }}" wire:model="nik" maxlength="16" />
                            <x-core::input label="{{ __('Nomor HP') }}" wire:model="phone" />
                            <x-core::input label="{{ __('Nomor KK') }}" wire:model="nomor_kk" maxlength="16" />
                            <x-core::select-dropdown label="{{ __('Kecamatan') }}" model="district_code" :items="$districts" valueField="value" labelField="label" />
                            <x-core::select-dropdown label="{{ __('Desa/Kelurahan') }}" model="village_code" :items="$villages" valueField="value" labelField="label" />
                        </div>
                        <div class="mt-4">
                            <x-core::textarea label="{{ __('Alamat') }}" wire:model="alamat" rows="2" />
                        </div>
                        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('KTP') }}</label>
                                <input type="file" wire:model="ktp" accept=".jpg,.jpeg,.png,.pdf" class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('KK') }}</label>
                                <input type="file" wire:model="kk" accept=".jpg,.jpeg,.png,.pdf" class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            </div>
                        </div>
                        @if($errors->any())
                            <ul class="mt-3 list-inside list-disc text-sm text-rose-600 dark:text-rose-400">
                                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                            </ul>
                        @endif
                        <div class="mt-4">
                            <x-core::button type="submit" label="{{ __('Simpan & Lanjut') }}" />
                        </div>
                    </form>
                @endif
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Alur Pendaftaran') }}</h2>
                <ol class="mt-3 list-decimal space-y-2 ps-4 text-xs text-gray-600 dark:text-gray-300">
                    <li>{{ __('Cari warga dengan NIK/KK/HP.') }}</li>
                    <li>{{ __('Buat data warga bila belum ada; KTP/KK cukup sekali.') }}</li>
                    <li>{{ __('Pilih layanan, isi keperluan dan persyaratan.') }}</li>
                    <li>{{ __('Selesaikan di meja atau teruskan ke bidang.') }}</li>
                </ol>
            </div>
        </div>
    @endif

    @if($step === 2)
        <div class="space-y-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $citizen?->nama }}</h2>
                        <p class="font-mono text-xs text-gray-500 dark:text-gray-400">NIK {{ $citizen?->maskedNik() }} · {{ $citizen?->familyCard?->village_name }}</p>
                    </div>
                    <button type="button" wire:click="$set('step', 1)" class="text-xs font-semibold text-purple-600 hover:underline dark:text-purple-400">{{ __('Ganti warga') }}</button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($services as $item)
                    <button type="button" wire:key="service-card-{{ $item->getKey() }}" wire:click="selectService('{{ $item->getKey() }}')"
                        class="group rounded-xl border border-gray-200 bg-white p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-purple-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-purple-700">
                        <div class="flex size-11 items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-purple-700 text-white shadow-lg shadow-purple-500/30">
                            <i data-lucide="{{ $item->icon }}" class="size-5"></i>
                        </div>
                        <h3 class="mt-3 text-sm font-semibold text-gray-900 group-hover:text-purple-600 dark:text-white dark:group-hover:text-purple-400">{{ $item->nama }}</h3>
                        <p class="mt-1 line-clamp-2 text-xs text-gray-500 dark:text-gray-400">{{ $item->deskripsi ?: $item->bidang?->nama }}</p>
                        <div class="mt-2 flex flex-wrap gap-1">
                            @if($item->selesai_di_pendaftaran)
                                <span class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ __('Selesai di meja') }}</span>
                            @endif
                            @if($item->butuh_verifikasi)
                                <span class="rounded bg-sky-50 px-1.5 py-0.5 text-[10px] text-sky-700 dark:bg-sky-900/20 dark:text-sky-300">{{ __('Verlap') }}</span>
                            @endif
                        </div>
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    @if($step === 3 && $service)
        <form wire:submit="forward" class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="flex size-10 items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-purple-700 text-white">
                                <i data-lucide="{{ $service->icon }}" class="size-5"></i>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $service->nama }}</h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $service->bidang?->nama }}</p>
                            </div>
                        </div>
                        <button type="button" wire:click="$set('step', 2)" class="text-xs font-semibold text-purple-600 hover:underline dark:text-purple-400">{{ __('Ganti layanan') }}</button>
                    </div>

                    <div class="mt-4 space-y-4">
                        <x-core::textarea label="{{ __('Keperluan') }}" wire:model="keperluan" rows="2" />
                        @include('sidulur::partials.dynamic-fields', ['fields' => $service->form?->schema['fields'] ?? [], 'modelPrefix' => 'formData'])
                    </div>
                </div>

                @if(count($service->form?->schema['persyaratan'] ?? []))
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-5">
                        <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">{{ __('Persyaratan') }}</h3>
                        <div class="space-y-2">
                            @foreach($service->form->schema['persyaratan'] as $index => $syarat)
                                @php $kode = $syarat['kode'] ?? $syarat['key']; @endphp
                                <label wire:key="syarat-check-{{ $index }}" class="flex items-start gap-2 rounded-lg border border-gray-100 px-3 py-2 text-sm dark:border-gray-700">
                                    <x-core::checkbox wire:model="requirements.{{ $kode }}" />
                                    <span>
                                        <span class="font-medium text-gray-800 dark:text-gray-200">{{ $syarat['nama'] ?? $syarat['label'] }}</span>
                                        @if(! empty($syarat['sumber']))
                                            <span class="ml-1 rounded bg-gray-100 px-1.5 py-0.5 text-[10px] text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ __('master') }} {{ $syarat['sumber'] }}</span>
                                        @endif
                                        @if(! empty($syarat['keterangan']))
                                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $syarat['keterangan'] }}</span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="space-y-4">
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-5">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Penyelesaian') }}</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Pilih salah satu. Diteruskan selalu membuat antrean bidang.') }}</p>
                    <div class="mt-4 flex flex-col gap-2">
                        @if($service->selesai_di_pendaftaran)
                            <x-core::button type="button" wire:click="finishDesk" variant="success" label="{{ __('Selesaikan di Pendaftaran') }}" />
                        @endif
                        <x-core::button type="submit" label="{{ __('Kirim ke Bidang') }}" />
                        <x-core::secondary-button type="button" wire:click="$set('step', 2)" label="{{ __('Kembali') }}" />
                    </div>
                    <p class="mt-3 text-[11px] text-gray-400 dark:text-gray-500">{{ __('Nomor tiket dibuat otomatis saat disimpan.') }}</p>
                </div>
            </div>
        </form>
    @endif
</div>
