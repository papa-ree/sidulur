@php
    $tone = $record->status === 'selesai'
        ? 'bg-teal-50 text-teal-700 border-teal-100 dark:bg-teal-900/20 dark:text-teal-300 dark:border-teal-800/40'
        : 'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800/40';
@endphp

<tr wire:key="pendaftaran-row-{{ $record->getKey() }}" class="hover:bg-gray-50/80 transition-colors duration-150 dark:hover:bg-gray-800/50">
    <td class="w-full max-w-0 px-4 py-3.5 sm:w-auto sm:max-w-none">
        <a href="{{ route('sidulur.pendaftaran.show', $record->getKey()) }}" wire:navigate class="font-mono text-sm font-semibold text-purple-600 hover:underline dark:text-purple-400">{{ $record->ticket_number }}</a>
        <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $record->citizen?->nama }}</div>
    </td>
    <td class="hidden px-4 py-3.5 lg:table-cell">
        <div class="text-sm text-gray-700 dark:text-gray-300">{{ $record->service?->nama }}</div>
        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $record->service?->bidang?->nama }}</div>
    </td>
    <td class="hidden px-4 py-3.5 sm:table-cell">
        <span class="inline-flex rounded-md border px-2 py-0.5 text-xs font-medium {{ $tone }}">{{ str($record->status)->title() }}</span>
        @if($record->forwarded_at)
            <div class="mt-1 text-[10px] text-gray-400 dark:text-gray-500">{{ __('Diteruskan') }}</div>
        @endif
    </td>
    <td class="hidden px-4 py-3.5 md:table-cell">
        <div class="text-sm text-gray-700 dark:text-gray-300">{{ $record->created_at?->format('d M Y H:i') }}</div>
    </td>
    <td class="w-px whitespace-nowrap px-4 py-3.5">
        <a href="{{ route('sidulur.pendaftaran.show', $record->getKey()) }}" wire:navigate class="text-xs font-semibold text-purple-600 hover:underline dark:text-purple-400">{{ __('Detail') }}</a>
    </td>
</tr>
