@php
    $tone = fn (string $status) => match ($status) {
        'selesai', 'verlap_selesai' => 'bg-teal-50 text-teal-700 border-teal-100 dark:bg-teal-900/20 dark:text-teal-300 dark:border-teal-800/40',
        'dijadwalkan' => 'bg-sky-50 text-sky-700 border-sky-100 dark:bg-sky-900/20 dark:text-sky-300 dark:border-sky-800/40',
        default => 'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800/40',
    };
@endphp

<div class="space-y-4">
    <x-core::page-header :title="__('Pelayanan Bidang')" :subtitle="__('Antrean FIFO sesuai bidang yang ditugaskan.')" />

    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-700">
            <div>
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Antrean Aktif') }}</h2>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('Baru dan hasil verlap yang menunggu respon.') }}</p>
            </div>
            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-amber-700 dark:bg-amber-900/20 dark:text-amber-300">{{ $queue->count() }} {{ __('tiket') }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($queue as $item)
                        <tr wire:key="pelayanan-{{ $item->getKey() }}" class="hover:bg-gray-50/80 dark:hover:bg-gray-700/40">
                            <td class="w-full max-w-0 px-5 py-3.5">
                                <a href="{{ route('sidulur.pelayanan.show', $item->getKey()) }}" wire:navigate class="font-mono text-sm font-semibold text-purple-600 hover:underline dark:text-purple-400">{{ $item->pendaftaran?->ticket_number }}</a>
                                <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $item->pendaftaran?->citizen?->nama }}</div>
                            </td>
                            <td class="hidden px-5 py-3.5 lg:table-cell">
                                <div class="text-sm text-gray-700 dark:text-gray-300">{{ $item->service?->nama }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $item->bidang?->nama }}</div>
                            </td>
                            <td class="hidden px-5 py-3.5 sm:table-cell">
                                <span class="inline-flex rounded-md border px-2 py-0.5 text-xs font-medium {{ $tone($item->status) }}">{{ str($item->status)->replace('_', ' ')->title() }}</span>
                            </td>
                            <td class="hidden px-5 py-3.5 md:table-cell text-xs text-gray-500 dark:text-gray-400">{{ $item->created_at?->format('d M Y H:i') }}</td>
                            <td class="w-px whitespace-nowrap px-5 py-3.5">
                                <a href="{{ route('sidulur.pelayanan.show', $item->getKey()) }}" wire:navigate class="text-xs font-semibold text-purple-600 hover:underline dark:text-purple-400">{{ __('Proses') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('Antrean kosong.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Riwayat Terbaru') }}</h2>
        </div>
        <ul class="divide-y divide-gray-100 dark:divide-gray-700">
            @forelse($history as $item)
                <li class="flex items-center justify-between gap-3 px-5 py-3">
                    <div>
                        <a href="{{ route('sidulur.pelayanan.show', $item->getKey()) }}" wire:navigate class="font-mono text-sm text-purple-600 hover:underline dark:text-purple-400">{{ $item->pendaftaran?->ticket_number }}</a>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $item->pendaftaran?->citizen?->nama }} · {{ $item->service?->nama }}</p>
                    </div>
                    <span class="rounded-md border px-2 py-0.5 text-xs font-medium {{ $tone($item->status) }}">{{ str($item->status)->replace('_', ' ')->title() }}</span>
                </li>
            @empty
                <li class="px-5 py-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('Belum ada riwayat.') }}</li>
            @endforelse
        </ul>
    </div>
</div>
