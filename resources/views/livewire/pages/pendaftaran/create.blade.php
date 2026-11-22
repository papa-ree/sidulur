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
                            <x-core::input label="{{ __('Nama lengkap *') }}" wire:model="nama" />
                            <x-core::input label="{{ __('NIK *') }}" wire:model="nik" maxlength="16" />
                            <x-core::input label="{{ __('Nomor HP *') }}" wire:model="phone" />
                            <x-core::input label="{{ __('Nomor KK *') }}" wire:model="nomor_kk" maxlength="16" />
                            <x-core::select-dropdown label="{{ __('Kecamatan *') }}" model="district_code" :items="$districts" valueField="value" labelField="label" />
                            <x-core::select-dropdown label="{{ __('Desa/Kelurahan *') }}" model="village_code" :items="$villages" valueField="value" labelField="label" />
                        </div>
                        <div class="mt-4">
                            <x-core::textarea label="{{ __('Alamat *') }}" wire:model="alamat" rows="2" />
                        </div>
                        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <x-core::upload-zone wire:model="ktp" accept=".jpg,.jpeg,.png,.pdf" maxSize="4096"
                                    :label="__('KTP *')" :hint="__('JPG, PNG, PDF · maks 4 MB')" />
                                <x-core::input-error for="ktp" class="mt-1.5" />
                            </div>
                            <div>
                                <x-core::upload-zone wire:model="kk" accept=".jpg,.jpeg,.png,.pdf" maxSize="4096"
                                    :label="__('KK *')" :hint="__('JPG, PNG, PDF · maks 4 MB')" />
                                <x-core::input-error for="kk" class="mt-1.5" />
                            </div>
                        </div>
                        @if($errors->any())
                            <ul class="mt-3 list-inside list-disc text-sm text-rose-600 dark:text-rose-400">
                                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                            </ul>
                        @endif
                        <div class="mt-4">
                            <x-core::button type="submit" :label="__('Simpan & Lanjut')" />
                        </div>
                    </form>
                @endif
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Alur Pendaftaran') }}</h2>
                <ol class="mt-3 list-decimal space-y-2 ps-4 text-xs text-gray-600 dark:text-gray-300">
                    <li>{{ __('Cari warga dengan NIK/KK/HP.') }}</li>
                    <li>{{ __('Buat data warga bila belum ada; KTP/KK cukup sekali.') }}</li>
                    <li>{{ __('Pilih layanan, isi formulir dan persyaratan.') }}</li>
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

            {{-- Langkah 3 baru dirender setelah server menjawab, jadi skeleton ini
                 menjadi balasan klik kartu layanan. --}}
            <div wire:loading wire:target="selectService" class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div class="space-y-4 lg:col-span-2">
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-5">
                        <div class="flex items-center gap-3">
                            <div class="size-10 animate-pulse rounded-xl bg-gray-100 dark:bg-gray-700"></div>
                            <div class="space-y-1.5">
                                <div class="h-4 w-40 animate-pulse rounded bg-gray-100 dark:bg-gray-700"></div>
                                <div class="h-3 w-24 animate-pulse rounded bg-gray-100 dark:bg-gray-700"></div>
                            </div>
                        </div>
                        <div class="mt-4 space-y-3">
                            @for($i = 0; $i < 4; $i++)
                                <div class="h-11 animate-pulse rounded-xl bg-gray-100 dark:bg-gray-700"></div>
                            @endfor
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-5">
                        <div class="h-4 w-28 animate-pulse rounded bg-gray-100 dark:bg-gray-700"></div>
                        <div class="mt-4 space-y-2">
                            @for($i = 0; $i < 3; $i++)
                                <div class="h-10 animate-pulse rounded-lg bg-gray-100 dark:bg-gray-700"></div>
                            @endfor
                        </div>
                    </div>
                </div>

                <p class="sr-only">{{ __('Memuat formulir layanan…') }}</p>
            </div>

            <div wire:loading.remove wire:target="selectService" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($services as $item)
                    <button type="button" wire:key="service-card-{{ $item->getKey() }}" wire:click="selectService('{{ $item->getKey() }}')"
                        @class([
                            'group relative rounded-xl border bg-white p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-purple-300 hover:shadow-md dark:bg-gray-800 dark:hover:border-purple-700',
                            'border-purple-500 ring-2 ring-purple-500/30 dark:border-purple-500' => $serviceId === $item->getKey(),
                            'border-gray-200 dark:border-gray-700' => $serviceId !== $item->getKey(),
                        ])>
                        @if($serviceId === $item->getKey())
                            <span class="absolute right-3 top-3 flex size-5 items-center justify-center rounded-full bg-purple-600 text-white">
                                <x-lucide-check class="size-3" />
                            </span>
                        @endif
                        <div class="flex size-11 items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-purple-700 text-white shadow-lg shadow-purple-500/30">
                            {!! \Bale\Sidulur\Support\Lucide::render($item->icon, 'size-5') !!}
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
                                {!! \Bale\Sidulur\Support\Lucide::render($service->icon, 'size-5') !!}
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $service->nama }}</h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $service->bidang?->nama }}</p>
                            </div>
                        </div>
                        <button type="button" wire:click="$set('step', 2)" class="text-xs font-semibold text-purple-600 hover:underline dark:text-purple-400">{{ __('Ganti layanan') }}</button>
                    </div>

                    <div class="mt-4 space-y-4">
                        @include('sidulur::partials.dynamic-fields', ['fields' => $service->form?->schema['fields'] ?? [], 'modelPrefix' => 'formData'])
                    </div>
                </div>

                @if($citizen)
                    @include('sidulur::partials.identity', [
                        'citizen' => $citizen,
                        'missing' => $identityMissing,
                        'checkable' => false,
                        'refreshable' => true,
                    ])
                @endif

                @if(count($requirements))
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-5">
                        <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">{{ __('Persyaratan Tambahan') }}</h3>
                        @include('sidulur::partials.requirements', [
                            'requirements' => $requirements,
                            'dataModel' => 'requirementData',
                            'filesModel' => 'requirementFiles',
                        ])
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
                    @error('identitas')
                        <p class="mt-2 rounded-lg bg-rose-50 px-3 py-2 text-xs text-rose-700 dark:bg-rose-900/20 dark:text-rose-300">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </form>
    @endif
</div>
