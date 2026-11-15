<div class="space-y-4">
    <x-core::page-header :title="__('Pendaftaran')" :subtitle="__('Tiket pelayanan warga.')">
        <x-slot name="action">
            @can('sidulur.pendaftaran.create')
                <x-core::button link href="{{ route('sidulur.pendaftaran.create') }}" label="{{ __('Pendaftaran Baru') }}">
                    <x-slot name="icon"><x-lucide-plus class="h-5 w-5" /></x-slot>
                </x-core::button>
            @endcan
        </x-slot>
    </x-core::page-header>

    <livewire:core-shared-components::data-table
        model="Bale\Sidulur\Models\Pendaftaran"
        rowView="sidulur::livewire.pages.pendaftaran.section.row"
        connectionResolver="Bale\Cms\Services\TenantConnectionService::resolveForQuery"
        :with="['citizen', 'service']"
        :searchable="['ticket_number']"
        :columns="[
            ['key' => 'ticket_number', 'label' => __('Tiket'), 'sortable' => true],
            ['key' => 'created_at', 'label' => __('Dibuat'), 'sortable' => true],
        ]"
        :filterOptions="[
            ['key' => 'status', 'label' => __('Status'), 'options' => ['baru' => __('Baru'), 'selesai' => __('Selesai')]],
        ]"
        searchPlaceholder="{{ __('Cari nomor tiket') }}" />
</div>
