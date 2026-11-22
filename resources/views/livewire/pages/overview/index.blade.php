<div class="space-y-4">
    <x-core::page-header gradient :title="__('Overview Sidulur')" :subtitle="__('Ringkasan pelayanan sosial periode berjalan.')">
        <x-slot name="action">
            @can('sidulur.pendaftaran.create')
                <x-core::button link href="{{ route('sidulur.pendaftaran.create') }}" label="{{ __('Pendaftaran Baru') }}">
                    <x-slot name="icon"><x-lucide-plus class="h-5 w-5" /></x-slot>
                </x-core::button>
            @endcan
        </x-slot>
    </x-core::page-header>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-core::stat-card-gradient label="{{ __('Total Pendaftaran') }}" :value="number_format($totalPendaftaran)" icon="ticket" tone="indigo" :hint="__('Hari ini').': '.number_format($todayPendaftaran)" />
        <x-core::stat-card-gradient label="{{ __('Antrean Bidang') }}" :value="number_format($antreanBidang)" icon="briefcase-business" tone="amber" :hint="__('Menunggu respon bidang')" />
        <x-core::stat-card-gradient label="{{ __('Antrean Verlap') }}" :value="number_format($antreanVerlap)" icon="map-pin" tone="sky" :hint="__('Sudah dijadwalkan')" />
        <x-core::stat-card-gradient label="{{ __('Selesai') }}" :value="number_format($selesai)" icon="circle-check" tone="emerald" :hint="__('Pelayanan bidang selesai')" />
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 lg:col-span-2">
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Pendaftaran Terbaru') }}</h2>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('Delapan tiket terakhir.') }}</p>
                </div>
                @can('sidulur.pendaftaran.view')
                    <a href="{{ route('sidulur.pendaftaran.index') }}" wire:navigate class="text-xs font-semibold text-purple-600 hover:underline dark:text-purple-400">{{ __('Lihat semua') }}</a>
                @endcan
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($recent as $item)
                            <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/40">
                                <td class="px-5 py-3 font-mono text-xs text-gray-900 dark:text-gray-100">{{ $item->ticket_number }}</td>
                                <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $item->citizen?->nama }}</td>
                                <td class="px-5 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $item->service?->nama }}</td>
                                <td class="px-5 py-3 text-xs text-gray-400 dark:text-gray-500">{{ $item->created_at?->format('d M Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('Belum ada pendaftaran.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Master Data') }}</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">{{ __('Warga') }}</dt><dd class="font-semibold tabular-nums text-gray-900 dark:text-white">{{ number_format($totalCitizen) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">{{ __('Bidang aktif') }}</dt><dd class="font-semibold tabular-nums text-gray-900 dark:text-white">{{ number_format($totalBidang) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">{{ __('Layanan aktif') }}</dt><dd class="font-semibold tabular-nums text-gray-900 dark:text-white">{{ number_format($totalService) }}</dd></div>
                </dl>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Layanan Teratas') }}</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    @forelse($topServices as $row)
                        <li class="flex items-center justify-between gap-3">
                            <span class="truncate text-gray-600 dark:text-gray-300">{{ $row->service?->nama ?? '—' }}</span>
                            <span class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold tabular-nums text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ $row->total }}</span>
                        </li>
                    @empty
                        <li class="text-gray-500 dark:text-gray-400">{{ __('Belum ada data.') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
