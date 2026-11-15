<div class="space-y-4">
    <x-core::page-header :title="__('Verifikasi Lapangan')" :subtitle="__('Antrean kunjungan sesuai jadwal dan bidang petugas.')" />

    <livewire:core-shared-components::data-table
        model="Bale\Sidulur\Models\PelayananBidang"
        rowView="sidulur::livewire.pages.verlap.section.row"
        connectionResolver="Bale\Cms\Services\TenantConnectionService::resolveForQuery"
        :with="['pendaftaran.citizen', 'service', 'bidang']"
        queryUsing="Bale\Sidulur\Support\PelayananTable::verlap"
        searchUsing="Bale\Sidulur\Models\PelayananBidang::searchForTable"
        :columns="[
            ['key' => 'created_at', 'label' => __('Tiket'), 'sortable' => true],
            ['key' => 'bidang_id', 'label' => __('Layanan'), 'hidden' => 'lg'],
            ['key' => 'scheduled_at', 'label' => __('Jadwal'), 'sortable' => true, 'hidden' => 'sm'],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ]"
        sortField="scheduled_at"
        sortDirection="asc"
        searchPlaceholder="{{ __('Cari tiket atau nama warga') }}" />
</div>
