<div class="space-y-4">
    <x-core::page-header :title="__($bidangId ? 'Ubah Bidang' : 'Tambah Bidang')" :subtitle="__('Unit kerja tujuan layanan.')" />

    <form wire:submit="save" class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
                <div class="space-y-4">
                    <x-core::input label="{{ __('Nama bidang') }}" wire:model="nama" />
                    <x-core::textarea label="{{ __('Deskripsi') }}" wire:model="deskripsi" rows="3" />
                    <x-core::input label="{{ __('Ikon Lucide') }}" wire:model="icon" placeholder="landmark" />
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                        <x-core::checkbox wire:model="is_active" />
                        {{ __('Aktif') }}
                    </label>
                </div>
                @error('nama') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                @error('icon') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                @error('deskripsi') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex flex-col gap-2">
            <x-core::button type="submit" label="{{ __('Simpan Bidang') }}" />
            <x-core::secondary-button link href="{{ route('sidulur.bidang.index') }}" label="{{ __('Batal') }}" />
        </div>
    </form>
</div>
