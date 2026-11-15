<div class="space-y-4">
    <x-core::page-header :title="__('Petugas')" :subtitle="__('Petugas dapat merangkap peran dan ditugaskan ke banyak bidang.')">
        <x-slot name="action">
            @can('sidulur.officer.create')
                <x-core::button link href="{{ route('sidulur.officer.form') }}" label="{{ __('Tambah Petugas') }}">
                    <x-slot name="icon"><x-lucide-plus class="h-5 w-5" /></x-slot>
                </x-core::button>
            @endcan
        </x-slot>
    </x-core::page-header>

    <livewire:core-shared-components::data-table
        model="Bale\Sidulur\Models\Officer"
        rowView="sidulur::livewire.pages.officer.section.row"
        connectionResolver="Bale\Cms\Services\TenantConnectionService::resolveForQuery"
        :with="['user', 'bidangs']"
        searchUsing="Bale\Sidulur\Models\Officer::searchForTable"
        :columns="[
            ['key' => 'user_id', 'label' => __('Petugas'), 'sortable' => false],
            ['key' => 'is_bidang', 'label' => __('Peran'), 'hidden' => 'md'],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ]"
        deleteEvent="deleteOfficer"
        searchPlaceholder="{{ __('Cari nama atau email petugas') }}" />
</div>
