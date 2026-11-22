<div class="space-y-4">
    <x-core::page-header :title="__('Data Warga')" :subtitle="__('Master warga, kartu keluarga, dan dokumen.')">
        <x-slot name="action">
            <div class="flex gap-2">
                @can('sidulur.citizen.delete')
                    <x-core::secondary-button link href="{{ route('sidulur.citizen.trashed') }}" label="{{ __('Data Terhapus') }}" />
                @endcan
                @can('sidulur.citizen.create')
                    <x-core::button link href="{{ route('sidulur.citizen.form') }}" label="{{ __('Tambah Warga') }}">
                        <x-slot name="icon"><x-lucide-plus class="h-5 w-5" /></x-slot>
                    </x-core::button>
                @endcan
            </div>
        </x-slot>
    </x-core::page-header>

    <livewire:core-shared-components::data-table
        model="Bale\Sidulur\Models\Citizen"
        rowView="sidulur::livewire.pages.citizen.section.row"
        connectionResolver="Bale\Cms\Services\TenantConnectionService::resolveForQuery"
        searchUsing="Bale\Sidulur\Models\Citizen::searchForTable"
        :searchable="['nama']"
        :with="['familyCard']"
        :columns="[
            ['key' => 'nama', 'label' => __('Nama'), 'sortable' => true],
            ['key' => 'created_at', 'label' => __('Terdaftar')],
        ]"
        deleteEvent="deleteCitizen"
        searchPlaceholder="{{ __('Cari nama, NIK, KK, atau HP') }}" />
</div>
