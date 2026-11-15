<div class="space-y-4">
    <x-core::page-header :title="__('Layanan')" :subtitle="__('Setiap layanan memiliki satu formulir dan satu bidang tujuan.')">
        <x-slot name="action">
            @can('sidulur.service.create')
                <x-core::button link href="{{ route('sidulur.service.form') }}" label="{{ __('Tambah Layanan') }}">
                    <x-slot name="icon"><x-lucide-plus class="h-5 w-5" /></x-slot>
                </x-core::button>
            @endcan
        </x-slot>
    </x-core::page-header>

    <livewire:core-shared-components::data-table
        model="Bale\Sidulur\Models\Service"
        rowView="sidulur::livewire.pages.service.section.row"
        connectionResolver="Bale\Cms\Services\TenantConnectionService::resolveForQuery"
        :with="['bidang']"
        :withCount="['pendaftarans']"
        :searchable="['nama', 'deskripsi']"
        :columns="[
            ['key' => 'nama', 'label' => __('Layanan'), 'sortable' => true],
            ['key' => 'bidang_id', 'label' => __('Bidang'), 'hidden' => 'md'],
            ['key' => 'sla_value', 'label' => __('SLA'), 'hidden' => 'lg'],
            ['key' => 'is_active', 'label' => __('Status'), 'hidden' => 'sm'],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ]"
        sortField="sort"
        sortDirection="asc"
        deleteEvent="deleteService"
        searchPlaceholder="{{ __('Cari layanan') }}" />
</div>
