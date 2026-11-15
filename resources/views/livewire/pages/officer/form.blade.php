<div class="space-y-4">
    <x-core::page-header :title="__($officerId ? 'Ubah Petugas' : 'Tambah Petugas')" :subtitle="__('Petugas dapat merangkap peran dan ditugaskan ke banyak bidang.')" />

    <form wire:submit="save" class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
                <h2 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">{{ __('Pengguna & Peran') }}</h2>
                <x-core::select-dropdown label="{{ __('Pengguna') }}" model="user_id" :items="$users->map(fn ($u) => ['value' => $u->uuid, 'label' => $u->name.' ('.$u->email.')'])->all()" valueField="value" labelField="label" placeholder="{{ __('Pilih pengguna') }}" />
                @error('user_id') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror

                <div class="mt-4 space-y-2">
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200"><x-core::checkbox wire:model="is_pendaftaran" /> {{ __('Petugas pendaftaran') }}</label>
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200"><x-core::checkbox wire:model="is_bidang" /> {{ __('Petugas bidang') }}</label>
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200"><x-core::checkbox wire:model="is_lapangan" /> {{ __('Petugas lapangan') }}</label>
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200"><x-core::checkbox wire:model="is_active" /> {{ __('Aktif') }}</label>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
                <h2 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">{{ __('Bidang yang ditugaskan') }}</h2>
                <div class="space-y-2">
                    @forelse($bidangs as $bidang)
                        <label wire:key="bidang-opt-{{ $bidang->getKey() }}" class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                            <x-core::checkbox wire:model="bidang_ids" value="{{ $bidang->getKey() }}" /> {{ $bidang->nama }}
                        </label>
                    @empty
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Belum ada bidang.') }}</p>
                    @endforelse
                </div>
                @error('bidang_ids') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex flex-col gap-2">
            <x-core::button type="submit" label="{{ __('Simpan Petugas') }}" />
            <x-core::secondary-button link href="{{ route('sidulur.officer.index') }}" label="{{ __('Batal') }}" />
        </div>
    </form>
</div>
