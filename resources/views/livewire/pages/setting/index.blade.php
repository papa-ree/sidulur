<div class="space-y-4">
    <x-core::page-header :title="__('Pengaturan Sidulur')" :subtitle="__('Masa berlaku tiket, tautan dokumen, dan password dashboard publik.')" />

    <form wire:submit="save" class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Masa Berlaku') }}</h2>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-core::input type="number" label="{{ __('Masa berlaku tiket (hari)') }}" wire:model="ticket_expiry_days" />
                    <x-core::input type="number" label="{{ __('Masa berlaku tautan dokumen (hari)') }}" wire:model="document_link_days" />
                </div>
                @error('ticket_expiry_days') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                @error('document_link_days') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Kontak') }}</h2>
                <div class="mt-4">
                    <x-core::input label="{{ __('Nomor WhatsApp pusat') }}" wire:model="whatsapp_center" placeholder="628xxxxxxxxxx" />
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Password Dashboard Eksekutif') }}</h2>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Password diminta setiap kunjungan dan tidak disimpan dalam sesi.') }}</p>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-core::input type="password" label="{{ __('Password baru') }}" wire:model="password" usePasswordField />
                    <x-core::input type="password" label="{{ __('Ulangi password') }}" wire:model="password_confirmation" usePasswordField />
                </div>
                @error('password') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <x-core::button type="submit" label="{{ __('Simpan Pengaturan') }}" />
                <p class="mt-3 text-[11px] text-gray-400 dark:text-gray-500">{{ __('Kosongkan password bila tidak ingin mengubah.') }}</p>
            </div>
        </div>
    </form>
</div>
