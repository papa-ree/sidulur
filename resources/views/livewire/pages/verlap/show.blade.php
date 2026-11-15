<div class="space-y-4">
    <x-core::page-header :title="$pelayanan->pendaftaran?->ticket_number" :subtitle="__('Formulir hasil verifikasi lapangan.')">
        <x-slot name="action">
            <x-core::secondary-button link href="{{ route('sidulur.verlap.index') }}" label="{{ __('Kembali') }}" />
        </x-slot>
    </x-core::page-header>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <form wire:submit="save" class="space-y-4 lg:col-span-2">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Data Pemohon') }}</h2>
                <dl class="mt-3 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Nama') }}</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $pelayanan->pendaftaran?->citizen?->nama }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('NIK') }}</dt><dd class="font-mono text-gray-900 dark:text-white">{{ $pelayanan->pendaftaran?->citizen?->maskedNik() }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Jadwal') }}</dt><dd class="text-gray-900 dark:text-white">{{ $pelayanan->scheduled_at?->format('d M Y') }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Layanan') }}</dt><dd class="text-gray-900 dark:text-white">{{ $pelayanan->service?->nama }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-gray-500 dark:text-gray-400">{{ __('Alamat') }}</dt><dd class="text-gray-900 dark:text-white">{{ $pelayanan->pendaftaran?->citizen?->familyCard?->alamat }}, {{ $pelayanan->pendaftaran?->citizen?->familyCard?->village_name }}, {{ $pelayanan->pendaftaran?->citizen?->familyCard?->district_name }}</dd></div>
                </dl>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Hasil Verifikasi') }}</h2>
                <div class="mt-3 space-y-4">
                    @include('sidulur::partials.dynamic-fields', ['fields' => $pelayanan->form_snapshot['verifikasi'] ?? [], 'modelPrefix' => 'verlapData'])
                    <x-core::textarea label="{{ __('Catatan') }}" wire:model="note" rows="2" />
                </div>
                <div class="mt-4">
                    <x-core::button type="submit" label="{{ __('Simpan Hasil') }}" />
                </div>
            </div>
        </form>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Catatan Jadwal') }}</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ $pelayanan->scheduled_note ?: '—' }}</p>
        </div>
    </div>
</div>
