<div class="space-y-4">
    <x-core::page-header :title="__($serviceId ? 'Ubah Layanan' : 'Tambah Layanan')" :subtitle="__('Setiap layanan memiliki satu formulir dan satu bidang tujuan.')" />

    <form wire:submit="save" class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
                <h2 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">{{ __('Identitas Layanan') }}</h2>
                <div class="space-y-4">
                    <x-core::select-dropdown label="{{ __('Bidang') }}" model="bidang_id" :items="$bidangs->map(fn ($b) => ['value' => $b->getKey(), 'label' => $b->nama])->all()" valueField="value" labelField="label" placeholder="{{ __('Pilih bidang') }}" />
                    <x-core::input label="{{ __('Nama layanan') }}" wire:model.live="nama" />
                    <x-core::input label="{{ __('Slug') }}" wire:model="slug" />
                    <x-core::input label="{{ __('Ikon Lucide') }}" wire:model="icon" />
                    <x-core::textarea label="{{ __('Deskripsi') }}" wire:model="deskripsi" rows="2" />
                </div>
                @error('bidang_id') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                @error('nama') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                @error('slug') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
                <h2 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">{{ __('Alur & SLA') }}</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-core::input label="{{ __('Nilai SLA') }}" type="number" wire:model="sla_value" />
                    <x-core::select-dropdown label="{{ __('Satuan') }}" model="sla_unit" :items="[['value' => 'jam', 'label' => __('Jam')], ['value' => 'hari', 'label' => __('Hari')]]" valueField="value" labelField="label" />
                </div>
                <div class="mt-4 space-y-2">
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                        <x-core::checkbox wire:model="selesai_di_pendaftaran" /> {{ __('Bisa selesai di pendaftaran') }}
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                        <x-core::checkbox wire:model="butuh_verifikasi" /> {{ __('Butuh verifikasi lapangan') }}
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                        <x-core::checkbox wire:model="is_active" /> {{ __('Aktif') }}
                    </label>
                </div>
                @error('sla_value') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                @error('sla_unit') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex flex-col gap-2">
            <x-core::button type="submit" label="{{ __('Simpan Layanan') }}" />
            <x-core::secondary-button link href="{{ route('sidulur.service.index') }}" label="{{ __('Batal') }}" />
        </div>
    </form>
</div>
