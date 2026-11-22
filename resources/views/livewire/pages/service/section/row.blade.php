<tr wire:key="service-row-{{ $record->getKey() }}" class="hover:bg-gray-50/80 transition-colors duration-150 dark:hover:bg-gray-800/50">
    <td class="w-full max-w-0 px-4 py-3.5 sm:w-auto sm:max-w-none">
        <div class="flex items-center gap-2">
            {!! \Bale\Sidulur\Support\Lucide::render($record->icon, 'size-4 shrink-0 text-gray-500 dark:text-gray-400') !!}
            <div class="min-w-0">
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $record->nama }}</div>
                <div class="flex flex-wrap gap-1 pt-1">
                    @if($record->selesai_di_pendaftaran)
                        <span class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ __('Selesai di meja') }}</span>
                    @endif
                    @if($record->butuh_verifikasi)
                        <span class="rounded bg-sky-50 px-1.5 py-0.5 text-[10px] font-medium text-sky-700 dark:bg-sky-900/20 dark:text-sky-300">{{ __('Verlap') }}</span>
                    @endif
                    <span class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ $record->pendaftarans_count }} {{ __('pengajuan') }}</span>
                </div>
            </div>
        </div>
    </td>
    <td class="hidden px-4 py-3.5 text-sm text-gray-700 dark:text-gray-300 md:table-cell">
        {{ $record->bidang?->nama ?: '-' }}
    </td>
    <td class="hidden px-4 py-3.5 text-sm text-gray-700 dark:text-gray-300 lg:table-cell">
        {{ $record->sla_value ? $record->sla_value.' '.$record->sla_unit : '-' }}
    </td>
    <td class="hidden px-4 py-3.5 sm:table-cell">
        <span class="inline-flex rounded-md border px-2 py-0.5 text-xs font-medium {{ $record->is_active ? 'border-teal-100 bg-teal-50 text-teal-700 dark:border-teal-800/40 dark:bg-teal-900/20 dark:text-teal-300' : 'border-gray-200 bg-gray-100 text-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">{{ $record->is_active ? __('Aktif') : __('Nonaktif') }}</span>
    </td>
    <td class="w-px whitespace-nowrap px-4 py-3.5">
        <livewire:core.shared-components.item-actions
            :editUrl="auth()->user()->can('sidulur.service.update') ? route('sidulur.service.form', $record->getKey()) : null"
            :deleteId="auth()->user()->can('sidulur.service.delete') ? $record->getKey() : null"
            deleteEvent="deleteService"
            modelName="Layanan"
            confirmMessage="{{ __('Yakin ingin menghapus layanan ini?') }}"
            wire:key="service-actions-{{ $record->getKey() }}" />
    </td>
</tr>
