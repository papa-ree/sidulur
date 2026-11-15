<tr wire:key="verlap-row-{{ $record->getKey() }}" class="hover:bg-gray-50/80 transition-colors duration-150 dark:hover:bg-gray-800/50">
    <td class="w-full max-w-0 px-4 py-3.5 sm:w-auto sm:max-w-none">
        <a href="{{ route('sidulur.verlap.show', $record->getKey()) }}" wire:navigate class="font-mono text-sm font-semibold text-purple-600 hover:underline dark:text-purple-400">{{ $record->pendaftaran?->ticket_number }}</a>
        <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $record->pendaftaran?->citizen?->nama }}</div>
    </td>
    <td class="hidden px-4 py-3.5 lg:table-cell">
        <div class="text-sm text-gray-700 dark:text-gray-300">{{ $record->service?->nama }}</div>
        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $record->bidang?->nama }}</div>
    </td>
    <td class="hidden px-4 py-3.5 sm:table-cell">
        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $record->scheduled_at?->format('d M Y') }}</div>
        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $record->scheduled_note }}</div>
    </td>
    <td class="w-px whitespace-nowrap px-4 py-3.5">
        <a href="{{ route('sidulur.verlap.show', $record->getKey()) }}" wire:navigate class="text-xs font-semibold text-purple-600 hover:underline dark:text-purple-400">{{ __('Isi Hasil') }}</a>
    </td>
</tr>
