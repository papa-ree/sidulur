<tr wire:key="citizen-row-{{ $record->getKey() }}" class="hover:bg-gray-50/80 transition-colors duration-150 dark:hover:bg-gray-800/50">
    <td class="w-full max-w-0 px-4 py-3.5 sm:w-auto sm:max-w-none">
        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $record->nama }}</div>
        <div class="mt-0.5 font-mono text-xs text-gray-500 dark:text-gray-400">NIK {{ $record->maskedNik() }}</div>
    </td>
    <td class="hidden px-4 py-3.5 lg:table-cell">
        <div class="font-mono text-xs text-gray-700 dark:text-gray-300">{{ $record->familyCard?->nomor_kk ? '••••'.substr($record->familyCard->nomor_kk, -4) : '—' }}</div>
        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $record->familyCard?->village_name }}, {{ $record->familyCard?->district_name }}</div>
    </td>
    <td class="hidden px-4 py-3.5 md:table-cell">
        <div class="text-sm text-gray-700 dark:text-gray-300">{{ $record->phone ?: '—' }}</div>
    </td>
    <td class="hidden px-4 py-3.5 sm:table-cell">
        <div class="text-sm text-gray-700 dark:text-gray-300">{{ $record->created_at?->format('d M Y') }}</div>
    </td>
    <td class="w-px whitespace-nowrap px-4 py-3.5">
        <livewire:core.shared-components.item-actions
            :editUrl="route('sidulur.citizen.show', $record->getKey())"
            :deleteId="auth()->user()->can('sidulur.citizen.delete') ? $record->getKey() : null"
            deleteEvent="deleteCitizen"
            modelName="Warga"
            confirmMessage="{{ __('Yakin ingin menghapus data warga?') }}"
            wire:key="citizen-actions-{{ $record->getKey() }}" />
    </td>
</tr>
