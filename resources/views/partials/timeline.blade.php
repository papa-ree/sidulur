@php
    $tone = fn (string $status) => match ($status) {
        'selesai', 'verlap_selesai' => 'bg-teal-50 text-teal-700 border-teal-100 dark:bg-teal-900/20 dark:text-teal-300 dark:border-teal-800/40',
        'baru' => 'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800/40',
        'dijadwalkan' => 'bg-sky-50 text-sky-700 border-sky-100 dark:bg-sky-900/20 dark:text-sky-300 dark:border-sky-800/40',
        default => 'bg-gray-100 text-gray-600 border-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600',
    };
    $label = fn (string $status) => str($status)->replace('_', ' ')->title();
@endphp

<ol class="relative space-y-4 border-s border-gray-200 ps-5 dark:border-gray-700">
    @forelse($timelines as $timeline)
        <li class="relative">
            <span class="absolute -start-[27px] top-1 flex size-3.5 items-center justify-center rounded-full border-2 border-white bg-purple-500 dark:border-gray-800"></span>
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm font-semibold text-gray-900 dark:text-white">
                    {{ str($timeline->stage)->title() }}
                </span>
                <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-medium {{ $tone($timeline->to_status) }}">
                    {{ $label($timeline->to_status) }}
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $timeline->created_at?->format('d M Y H:i') }}
                </span>
            </div>
            @if($timeline->note)
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $timeline->note }}</p>
            @endif
        </li>
    @empty
        <li class="text-sm text-gray-500 dark:text-gray-400">{{ __('Belum ada riwayat.') }}</li>
    @endforelse
</ol>
