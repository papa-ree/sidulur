<div class="space-y-4">
    <x-core::page-header :title="__('Bidang')" :subtitle="__('Unit kerja tujuan layanan.')">
        <x-slot name="action">
            @can('sidulur.bidang.create')
                <x-core::button link href="{{ route('sidulur.bidang.form') }}" label="{{ __('Tambah Bidang') }}">
                    <x-slot name="icon"><x-lucide-plus class="h-5 w-5" /></x-slot>
                </x-core::button>
            @endcan
        </x-slot>
    </x-core::page-header>

    <livewire:core-shared-components::data-table
        model="Bale\Sidulur\Models\Bidang"
        rowView="sidulur::livewire.pages.bidang.section.row"
        connectionResolver="Bale\Cms\Services\TenantConnectionService::resolveForQuery"
        :withCount="['services']"
        :searchable="['nama', 'deskripsi']"
        :columns="[
            ['key' => 'nama', 'label' => __('Bidang'), 'sortable' => true],
            ['key' => 'services_count', 'label' => __('Layanan'), 'hidden' => 'sm'],
            ['key' => 'is_active', 'label' => __('Status'), 'hidden' => 'md'],
            ['key' => 'created_at', 'label' => __('Dibuat'), 'sortable' => true, 'hidden' => 'lg'],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ]"
        sortField="nama"
        sortDirection="asc"
        deleteEvent="deleteBidang"
        searchPlaceholder="{{ __('Cari bidang') }}" />
</div>
