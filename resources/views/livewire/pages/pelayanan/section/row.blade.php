@php
    $tone = match ($record->status) {
        'selesai', 'verlap_selesai' => 'bg-teal-50 text-teal-700 border-teal-100 dark:bg-teal-900/20 dark:text-teal-300 dark:border-teal-800/40',
        'dijadwalkan' => 'bg-sky-50 text-sky-700 border-sky-100 dark:bg-sky-900/20 dark:text-sky-300 dark:border-sky-800/40',
        default => 'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800/40',
    };
@endphp

<tr wire:key="pelayanan-row-{{ $record->getKey() }}" class="hover:bg-gray-50/80 transition-colors duration-150 dark:hover:bg-gray-800/50">
    <td class="w-full max-w-0 px-4 py-3.5 sm:w-auto sm:max-w-none">
        <a href="{{ route('sidulur.pelayanan.show', $record->getKey()) }}" wire:navigate class="font-mono text-sm font-semibold text-purple-600 hover:underline dark:text-purple-400">{{ $record->pendaftaran?->ticket_number }}</a>
        <div class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">
            {{ $record->pendaftaran?->citizen?->nama }}
            <span class="text-gray-400 dark:text-gray-500">· {{ $record->created_at?->diffForHumans(short: true) }}</span>
        </div>
    </td>
    <td class="hidden px-4 py-3.5 lg:table-cell">
        <div class="max-w-xs truncate text-sm text-gray-700 dark:text-gray-300">{{ $record->service?->nama ?? '—' }}</div>
        <div class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $record->bidang?->nama }}</div>
    </td>
    <td class="hidden whitespace-nowrap px-4 py-3.5 sm:table-cell">
        <span class="inline-flex rounded-md border px-2 py-0.5 text-xs font-medium {{ $tone }}">{{ str($record->status)->replace('_', ' ')->title() }}</span>
        @if($record->service?->butuh_verifikasi)
            <span class="ml-1 inline-flex rounded bg-sky-50 px-1.5 py-0.5 text-[10px] text-sky-700 dark:bg-sky-900/20 dark:text-sky-300">{{ __('verlap') }}</span>
        @endif
        @if($record->completed_at)
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $record->completed_at->format('d M Y') }}</div>
        @elseif($record->scheduled_at)
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('jadwal') }} {{ $record->scheduled_at->format('d M Y') }}</div>
        @endif
    </td>
    <td class="w-px whitespace-nowrap px-4 py-3.5">
        <livewire:core.shared-components.item-actions
            :editUrl="route('sidulur.pelayanan.show', $record->getKey())"
            wire:key="pelayanan-actions-{{ $record->getKey() }}" />
    </td>
</tr>
