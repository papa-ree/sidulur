<tr wire:key="officer-row-{{ $record->getKey() }}" class="hover:bg-gray-50/80 transition-colors duration-150 dark:hover:bg-gray-800/50">
    <td class="w-full max-w-0 px-4 py-3.5 sm:w-auto sm:max-w-none">
        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $record->user?->name ?: '-' }}</div>
        <div class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $record->user?->email }}</div>
    </td>
    <td class="hidden px-4 py-3.5 text-xs text-gray-600 dark:text-gray-300 md:table-cell">
        <div>{{ collect([$record->is_pendaftaran ? 'Pendaftaran' : null, $record->is_bidang ? 'Bidang' : null, $record->is_lapangan ? 'Lapangan' : null])->filter()->join(', ') ?: '-' }}</div>
        <div class="mt-0.5 text-gray-500 dark:text-gray-400">{{ $record->bidangs->pluck('nama')->join(', ') ?: '-' }}</div>
        @unless($record->is_active)
            <span class="mt-1 inline-flex rounded-md border border-gray-200 bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ __('Nonaktif') }}</span>
        @endunless
    </td>
    <td class="w-px whitespace-nowrap px-4 py-3.5">
        <livewire:core.shared-components.item-actions
            :editUrl="auth()->user()->can('sidulur.officer.update') ? route('sidulur.officer.form', $record->getKey()) : null"
            :deleteId="auth()->user()->can('sidulur.officer.delete') ? $record->getKey() : null"
            deleteEvent="deleteOfficer"
            modelName="Petugas"
            confirmMessage="{{ __('Yakin ingin menghapus petugas ini?') }}"
            wire:key="officer-actions-{{ $record->getKey() }}" />
    </td>
</tr>
