<tr wire:key="citizen-trashed-row-{{ $record->getKey() }}" class="transition-colors duration-150 hover:bg-gray-50/80 dark:hover:bg-gray-800/50">
    <td class="w-full max-w-0 px-4 py-3.5 sm:w-auto sm:max-w-none">
        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $record->nama }}</div>
        <div class="mt-0.5 font-mono text-xs text-gray-500 dark:text-gray-400">NIK {{ $record->maskedNik() }}</div>
    </td>
    <td class="hidden px-4 py-3.5 lg:table-cell">
        <div class="font-mono text-xs text-gray-700 dark:text-gray-300">{{ $record->familyCard?->nomor_kk ? '••••'.substr($record->familyCard->nomor_kk, -4) : '—' }}</div>
        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $record->familyCard?->village_name }}, {{ $record->familyCard?->district_name }}</div>
    </td>
    <td class="hidden px-4 py-3.5 sm:table-cell">
        <div class="text-sm text-gray-700 dark:text-gray-300">{{ $record->deleted_at?->format('d M Y H:i') }}</div>
    </td>
    <td class="w-px whitespace-nowrap px-4 py-3.5">
        @can('sidulur.citizen.delete')
            <button type="button"
                wire:click="$dispatch('restoreCitizen', { id: '{{ $record->getKey() }}' })"
                wire:confirm="{{ __('Pulihkan data warga ini?') }}"
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-100 px-3 py-1.5 text-xs font-semibold text-emerald-700 transition-all hover:bg-emerald-200 disabled:opacity-50 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300 dark:hover:bg-emerald-900/50">
                <x-lucide-rotate-ccw class="w-3.5 h-3.5" />
                {{ __('Pulihkan') }}
            </button>
        @endcan
    </td>
</tr>
