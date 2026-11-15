<tr wire:key="bidang-row-{{ $record->getKey() }}" class="hover:bg-gray-50/80 transition-colors duration-150 dark:hover:bg-gray-800/50">
    <td class="w-full max-w-0 px-4 py-3.5 sm:w-auto sm:max-w-none">
        <div class="flex items-center gap-2">
            <i data-lucide="{{ $record->icon }}" class="size-4 shrink-0 text-gray-500 dark:text-gray-400"></i>
            <div class="min-w-0">
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $record->nama }}</div>
                <div class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $record->deskripsi ?: '-' }}</div>
            </div>
        </div>
    </td>
    <td class="hidden px-4 py-3.5 text-sm tabular-nums text-gray-700 dark:text-gray-300 sm:table-cell">
        {{ $record->services_count }}
    </td>
    <td class="hidden px-4 py-3.5 md:table-cell">
        <span class="inline-flex rounded-md border px-2 py-0.5 text-xs font-medium {{ $record->is_active ? 'border-teal-100 bg-teal-50 text-teal-700 dark:border-teal-800/40 dark:bg-teal-900/20 dark:text-teal-300' : 'border-gray-200 bg-gray-100 text-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">{{ $record->is_active ? __('Aktif') : __('Nonaktif') }}</span>
    </td>
    <td class="hidden px-4 py-3.5 text-sm text-gray-700 dark:text-gray-300 lg:table-cell">
        {{ $record->created_at?->format('d M Y') }}
    </td>
    <td class="w-px whitespace-nowrap px-4 py-3.5">
        <livewire:core.shared-components.item-actions
            :editUrl="auth()->user()->can('sidulur.bidang.update') ? route('sidulur.bidang.form', $record->getKey()) : null"
            :deleteId="auth()->user()->can('sidulur.bidang.delete') ? $record->getKey() : null"
            deleteEvent="deleteBidang"
            modelName="Bidang"
            confirmMessage="{{ __('Yakin ingin menghapus bidang ini?') }}"
            wire:key="bidang-actions-{{ $record->getKey() }}" />
    </td>
</tr>
