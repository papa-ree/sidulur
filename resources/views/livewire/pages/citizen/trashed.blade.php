<div class="space-y-4">
    <x-core::page-header :title="__('Data Warga Terhapus')" :subtitle="__('Warga yang dihapus tetap tersimpan dan dapat dipulihkan.')">
        <x-slot name="action">
            @can('sidulur.citizen.view')
                <x-core::secondary-button link href="{{ route('sidulur.citizen.index') }}" label="{{ __('Kembali ke Data Warga') }}" />
            @endcan
        </x-slot>
    </x-core::page-header>

    <livewire:core-shared-components::data-table
        model="Bale\Sidulur\Models\Citizen"
        rowView="sidulur::livewire.pages.citizen.section.trashed-row"
        connectionResolver="Bale\Cms\Services\TenantConnectionService::resolveForQuery"
        queryUsing="Bale\Sidulur\Support\CitizenTable::trashed"
        searchUsing="Bale\Sidulur\Models\Citizen::searchForTable"
        :searchable="['nama']"
        :with="['familyCard']"
        :columns="[
            ['key' => 'nama', 'label' => __('Nama'), 'sortable' => true],
            ['key' => 'deleted_at', 'label' => __('Dihapus'), 'sortable' => true],
        ]"
        sortField="deleted_at"
        sortDirection="desc"
        searchPlaceholder="{{ __('Cari nama, NIK, KK, atau HP') }}" />
</div>
