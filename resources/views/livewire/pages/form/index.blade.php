<div class="space-y-4">
    <x-core::page-header :title="__('Formulir Layanan')" :subtitle="__('Persyaratan, field respon bidang, dan field verifikasi lapangan.')" />

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($services as $service)
            <a href="{{ route('sidulur.form.edit', $service->getKey()) }}" wire:navigate class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex size-11 items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-purple-700 text-white shadow-lg shadow-purple-500/30">
                        <i data-lucide="{{ $service->icon }}" class="size-5"></i>
                    </div>
                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">v{{ $service->form?->version ?? 1 }}</span>
                </div>
                <h3 class="mt-3 text-sm font-semibold text-gray-900 group-hover:text-purple-600 dark:text-white dark:group-hover:text-purple-400">{{ $service->nama }}</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $service->bidang?->nama }}</p>
            </a>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-gray-300 p-10 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('Belum ada layanan. Tambahkan layanan terlebih dahulu.') }}</div>
        @endforelse
    </div>
</div>
