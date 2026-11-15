<div class="space-y-4">
    <x-core::page-header :title="__($citizenId ? 'Ubah Data Warga' : 'Tambah Warga')" :subtitle="__('NIK, KK, dan HP disimpan terenkripsi.')" />

    <form wire:submit="save" class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
                <h2 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">{{ __('Identitas Warga') }}</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-core::input label="{{ __('Nama lengkap') }}" wire:model="nama" />
                    <x-core::input label="{{ __('NIK') }}" wire:model="nik" maxlength="16" inputmode="numeric" />
                    <x-core::input label="{{ __('Nomor HP') }}" wire:model="phone" placeholder="08xxxxxxxxxx" />
                    <x-core::input label="{{ __('Nomor KK') }}" wire:model="nomor_kk" maxlength="16" inputmode="numeric" />
                </div>
                @error('nik') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                @error('phone') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                @error('nomor_kk') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
                <h2 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">{{ __('Alamat Kartu Keluarga') }}</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-core::select-dropdown label="{{ __('Kecamatan') }}" model="district_code" :items="$districts" valueField="value" labelField="label" placeholder="{{ __('Pilih kecamatan') }}" />
                    <x-core::select-dropdown label="{{ __('Desa/Kelurahan') }}" model="village_code" :items="$villages" valueField="value" labelField="label" placeholder="{{ __('Pilih desa/kelurahan') }}" />
                </div>
                <div class="mt-4">
                    <x-core::textarea label="{{ __('Alamat') }}" wire:model="alamat" rows="3" />
                </div>
                @error('district_code') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                @error('village_code') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                @error('alamat') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Anggota Keluarga') }}</h2>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Tambahkan anggota lain dalam satu KK. KTP disimpan per anggota; KK cukup satu per kartu keluarga.') }}</p>
                    </div>
                    <x-core::secondary-button type="button" wire:click="addAnggota" label="{{ __('Tambah') }}" />
                </div>

                <div class="mt-4 space-y-3">
                    @forelse($anggota as $index => $row)
                        <div wire:key="anggota-{{ $row['key'] }}" class="rounded-lg border border-gray-100 p-3 dark:border-gray-700">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Anggota') }} {{ $index + 1 }}</p>
                                <button type="button" wire:click="removeAnggota({{ $index }})" class="text-xs font-semibold text-rose-600 hover:underline dark:text-rose-400">{{ __('Hapus') }}</button>
                            </div>

                            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <x-core::input label="{{ __('NIK') }}" wire:model="anggota.{{ $index }}.nik" maxlength="16" inputmode="numeric" />
                                    @error("anggota.$index.nik") <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <x-core::input label="{{ __('Nama lengkap') }}" wire:model="anggota.{{ $index }}.nama" />
                                    @error("anggota.$index.nama") <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                @foreach(['ktp' => __('KTP'), 'kk' => __('KK')] as $kind => $label)
                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">{{ $label }}</label>
                                        <input type="file" wire:model="anggota.{{ $index }}.{{ $kind }}" accept=".jpg,.jpeg,.png,.pdf" class="block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                        <div wire:loading wire:target="anggota.{{ $index }}.{{ $kind }}" class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Mengunggah...') }}</div>
                                        @error("anggota.$index.$kind") <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Belum ada anggota baru. Klik Tambah untuk menambahkan.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
                <h2 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">{{ __('Dokumen') }}</h2>
                <div class="space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('KTP') }}</label>
                        <input type="file" wire:model="ktp" accept=".jpg,.jpeg,.png,.pdf" class="block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        <div wire:loading wire:target="ktp" class="mt-1 text-xs text-gray-500">{{ __('Mengunggah...') }}</div>
                        @error('ktp') <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Kartu Keluarga') }}</label>
                        <input type="file" wire:model="kk" accept=".jpg,.jpeg,.png,.pdf" class="block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        <div wire:loading wire:target="kk" class="mt-1 text-xs text-gray-500">{{ __('Mengunggah...') }}</div>
                        @error('kk') <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <x-core::button type="submit" label="{{ __('Simpan Warga') }}" />
                <x-core::secondary-button link href="{{ route('sidulur.citizen.index') }}" label="{{ __('Batal') }}" />
            </div>
        </div>
    </form>
</div>
