<div class="space-y-4">
    <x-core::page-header :title="__('Formulir Layanan')" :subtitle="__('Persyaratan, field respon bidang, dan field verifikasi lapangan.')" />

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($services as $service)
            <div wire:key="service-form-{{ $service->getKey() }}" class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex size-11 items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-purple-700 text-white shadow-lg shadow-purple-500/30">
                        {!! \Bale\Sidulur\Support\Lucide::render($service->icon, 'size-5') !!}
                    </div>
                    @if($service->form)
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">v{{ $service->form->version }}</span>
                    @else
                        <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-900/20 dark:text-amber-400">{{ __('Belum ada formulir') }}</span>
                    @endif
                </div>

                <h3 class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">{{ $service->nama }}</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $service->bidang?->nama }}</p>

                <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-3 dark:border-gray-700">
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $service->form ? __('Formulir tersedia') : __('Layanan ini belum punya formulir.') }}
                    </span>

                    @if($service->form)
                        @can('sidulur.form.view')
                            <a href="{{ route('sidulur.form.edit', $service->getKey()) }}" wire:navigate
                               class="text-xs font-semibold text-purple-600 hover:underline dark:text-purple-400">{{ __('Ubah formulir') }}</a>
                        @endcan
                    @else
                        @can('sidulur.form.update')
                            <a href="{{ route('sidulur.form.edit', $service->getKey()) }}" wire:navigate
                               class="text-xs font-semibold text-purple-600 hover:underline dark:text-purple-400">{{ __('Buat formulir') }}</a>
                        @endcan
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-gray-300 p-10 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('Belum ada layanan. Tambahkan layanan terlebih dahulu.') }}</div>
        @endforelse
    </div>
</div>
