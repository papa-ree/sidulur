<div class="space-y-4">
    <x-core::page-header
        :title="__($citizenId ? 'Ubah Data Warga' : 'Tambah Warga')"
        :subtitle="__('NIK, nomor KK, dan nomor HP disimpan terenkripsi.')" />

    {{-- Ringkasan galat: pengguna tidak perlu menggulir untuk tahu apa yang salah. --}}
    @if ($errors->any())
        <div role="alert" tabindex="-1"
             x-data
             x-init="$el.scrollIntoView({ behavior: 'smooth', block: 'center' }); $el.focus({ preventScroll: true })"
             class="flex gap-3 rounded-xl border border-rose-200 bg-rose-50 p-4 outline-none dark:border-rose-800/50 dark:bg-rose-900/20">
            <x-lucide-alert-circle class="mt-0.5 size-5 shrink-0 text-rose-500 dark:text-rose-400" />
            <div class="min-w-0">
                <p class="text-sm font-semibold text-rose-800 dark:text-rose-300">
                    {{ __('Data belum bisa disimpan. Periksa:') }}
                </p>
                <ul class="mt-1 list-disc space-y-0.5 pl-4 text-xs text-rose-700 dark:text-rose-400">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form wire:submit="save" class="grid grid-cols-1 items-start gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center gap-3 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        <x-lucide-user-round class="size-4" />
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Identitas warga') }}</h2>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('Isi sesuai KTP. Kolom bertanda * wajib diisi.') }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-x-4 gap-y-5 p-4 sm:grid-cols-2 sm:p-5">
                    <div class="sm:col-span-2">
                        <x-core::input label="{{ __('Nama lengkap *') }}" wire:model="nama" autocomplete="name" placeholder="{{ __('Nama sesuai KTP') }}" />
                        <x-core::input-error for="nama" class="mt-1.5" />
                    </div>

                    <div>
                        <x-core::input label="{{ __('NIK *') }}" wire:model="nik" x-mask="9999 9999 9999 9999" inputmode="numeric" autocomplete="off" placeholder="3502 1234 5678 0001" aria-describedby="nik-hint" />
                        <p id="nik-hint" class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ __('16 digit, tanpa spasi.') }}</p>
                        <x-core::input-error for="nik" class="mt-1.5" />
                    </div>

                    <div>
                        <x-core::input label="{{ __('Nomor KK *') }}" wire:model="nomor_kk" x-mask="9999 9999 9999 9999" inputmode="numeric" autocomplete="off" placeholder="3502 1234 5678 0001" aria-describedby="kk-hint" />
                        <p id="kk-hint" class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ __('16 digit pada Kartu Keluarga.') }}</p>
                        <x-core::input-error for="nomor_kk" class="mt-1.5" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-core::input label="{{ __('Nomor HP *') }}" wire:model="phone" x-mask="9999 9999 9999 9" inputmode="numeric" autocomplete="off" placeholder="0812 3456 7890" aria-describedby="phone-hint" />
                        <p id="phone-hint" class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ __('Dipakai untuk notifikasi WhatsApp.') }}</p>
                        <x-core::input-error for="phone" class="mt-1.5" />
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center gap-3 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        <x-lucide-map-pin class="size-4" />
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Alamat Kartu Keluarga') }}</h2>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('Alamat mengikuti KK, dipakai untuk verifikasi lapangan.') }}</p>
                    </div>
                </div>

                <div class="space-y-5 p-4 sm:p-5">
                    <div class="grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-2">
                        <div>
                            <x-core::select-dropdown label="{{ __('Kecamatan *') }}" model="district_code" :items="$districts" valueField="value" labelField="label" placeholder="{{ __('Pilih kecamatan') }}" />
                            <x-core::input-error for="district_code" class="mt-1.5" />
                        </div>
                        <div>
                            <x-core::select-dropdown label="{{ __('Desa/Kelurahan *') }}" model="village_code" :items="$villages" valueField="value" labelField="label" placeholder="{{ $district_code ? __('Pilih desa/kelurahan') : __('Pilih kecamatan lebih dulu') }}" />
                            <x-core::input-error for="village_code" class="mt-1.5" />
                        </div>
                    </div>

                    <div>
                        <x-core::textarea label="{{ __('Alamat *') }}" wire:model="alamat" rows="2" placeholder="{{ __('Jalan, RT/RW, nomor rumah') }}" aria-describedby="alamat-hint" />
                        <p id="alamat-hint" class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ __('Tulis nama jalan, RT/RW, dan nomor rumah.') }}</p>
                        <x-core::input-error for="alamat" class="mt-1.5" />
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
                 x-data="{ visible: 0, pool: {{ count($anggota) }} }">
                <div class="flex flex-col gap-3 border-b border-gray-200 px-5 py-4 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            <x-lucide-users class="size-4" />
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Anggota keluarga') }}</h2>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('Opsional — anggota lain dengan nomor KK yang sama.') }}</p>
                        </div>
                    </div>

                    {{-- Tambah baris sepenuhnya di Alpine; request hanya saat slot habis. --}}
                    <button type="button"
                        x-on:click="visible++; if (visible > pool) { pool++; $wire.addAnggota() }"
                        class="inline-flex shrink-0 items-center gap-1.5 self-start rounded-lg border border-purple-200 bg-purple-50 px-3 py-1.5 text-xs font-semibold text-purple-700 transition-all hover:border-purple-300 hover:bg-purple-100 dark:border-purple-800 dark:bg-purple-900/20 dark:text-purple-300 dark:hover:bg-purple-900/40 sm:self-auto">
                        <x-lucide-plus class="size-3.5" /> {{ __('Tambah anggota') }}
                    </button>
                </div>

                <div class="space-y-3 p-4 sm:p-5">
                    <div x-cloak x-show="visible === 0" class="flex items-center gap-3 rounded-lg bg-gray-50 px-4 py-3 dark:bg-gray-900/40">
                        <x-lucide-info class="size-4 shrink-0 text-gray-400 dark:text-gray-500" />
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            {{ __('Belum ada anggota baru. Data warga utama sudah cukup bila tidak ada anggota lain pada KK ini.') }}
                        </p>
                    </div>

                    @foreach($anggota as $index => $row)
                        <div x-cloak x-show="visible > {{ $index }}" wire:key="anggota-{{ $row['key'] }}"
                             class="rounded-lg border border-gray-200 bg-gray-50/60 p-3 dark:border-gray-700 dark:bg-gray-900/30 sm:p-4">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ __('Anggota') }} {{ $index + 1 }}
                                </p>
                                <button type="button"
                                    x-on:click="visible--; pool = Math.max(pool - 1, 0); $wire.removeAnggota({{ $index }})"
                                    aria-label="{{ __('Hapus anggota ini') }}"
                                    class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-semibold text-rose-600 transition-colors hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-900/20">
                                    <x-lucide-trash-2 class="size-3.5" /> {{ __('Hapus') }}
                                </button>
                            </div>

                            <div class="mt-3 grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                                <div>
                                    <x-core::input label="{{ __('NIK *') }}" wire:model="anggota.{{ $index }}.nik" x-mask="9999 9999 9999 9999" inputmode="numeric" autocomplete="off" placeholder="3502 1234 5678 0001" />
                                    <x-core::input-error for="anggota.{{ $index }}.nik" class="mt-1.5" />
                                </div>
                                <div>
                                    <x-core::input label="{{ __('Nama lengkap *') }}" wire:model="anggota.{{ $index }}.nama" autocomplete="off" placeholder="{{ __('Nama sesuai KTP') }}" />
                                    <x-core::input-error for="anggota.{{ $index }}.nama" class="mt-1.5" />
                                </div>
                            </div>

                            <details class="group mt-3">
                                <summary class="flex w-fit cursor-pointer list-none items-center gap-1.5 rounded-lg py-1 text-xs font-medium text-gray-600 hover:text-purple-600 dark:text-gray-400 dark:hover:text-purple-400">
                                    <x-lucide-paperclip class="size-3.5" />
                                    {{ __('Lampiran anggota (opsional)') }}
                                    <x-lucide-chevron-down class="size-3.5 transition-transform group-open:rotate-180" />
                                </summary>

                                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div>
                                        <x-core::upload-zone wire:model="anggota.{{ $index }}.ktp" accept=".jpg,.jpeg,.png,.pdf" maxSize="4096"
                                            :label="__('KTP anggota')" :hint="__('JPG, PNG, PDF · maks 4 MB')" />
                                        <x-core::input-error for="anggota.{{ $index }}.ktp" class="mt-1.5" />
                                    </div>
                                    <div>
                                        <x-core::upload-zone wire:model="anggota.{{ $index }}.kk" accept=".jpg,.jpeg,.png,.pdf" maxSize="4096"
                                            :label="__('KK anggota')" :hint="__('Hanya bila KK belum dilampirkan')" />
                                        <x-core::input-error for="anggota.{{ $index }}.kk" class="mt-1.5" />
                                    </div>
                                </div>
                            </details>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="space-y-4 lg:sticky lg:top-4">
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center gap-3 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        <x-lucide-paperclip class="size-4" />
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Dokumen pendukung') }}</h2>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('KTP dan KK wajib dilampirkan. Gambar dikompres otomatis ke WebP.') }}</p>
                    </div>
                </div>

                <div class="space-y-4 p-4 sm:p-5">
                    <div>
                        <x-core::upload-zone wire:model="ktp" accept=".jpg,.jpeg,.png,.pdf" maxSize="4096"
                            :label="$existing['ktp'] ? __('KTP warga') : __('KTP warga *')" :hint="__('JPG, PNG, PDF · maks 4 MB')" />
                        <x-core::input-error for="ktp" class="mt-1.5" />
                        @include('sidulur::partials.document-preview', ['document' => $existing['ktp'], 'label' => __('KTP warga tersimpan')])
                        @if ($existing['ktp'])
                            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">{{ __('Unggah berkas baru untuk mengganti.') }}</p>
                        @endif
                    </div>
                    <div>
                        <x-core::upload-zone wire:model="kk" accept=".jpg,.jpeg,.png,.pdf" maxSize="4096"
                            :label="$existing['kk'] ? __('Kartu Keluarga') : __('Kartu Keluarga *')" :hint="__('JPG, PNG, PDF · maks 4 MB')" />
                        <x-core::input-error for="kk" class="mt-1.5" />
                        @include('sidulur::partials.document-preview', ['document' => $existing['kk'], 'label' => __('Kartu Keluarga tersimpan')])
                        @if ($existing['kk'])
                            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">{{ __('Unggah berkas baru untuk mengganti.') }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <x-core::button type="submit" label="{{ __('Simpan Warga') }}" class="w-full" wire:loading.attr="disabled" wire:target="save">
                    <x-slot name="icon"><x-lucide-save class="size-4" /></x-slot>
                </x-core::button>
                <x-core::secondary-button link href="{{ route('sidulur.citizen.index') }}" label="{{ __('Batal') }}" class="w-full" />
                <p class="text-center text-xs text-gray-500 dark:text-gray-400" wire:loading wire:target="save">
                    {{ __('Menyimpan data…') }}
                </p>
            </div>
        </div>
    </form>
</div>
