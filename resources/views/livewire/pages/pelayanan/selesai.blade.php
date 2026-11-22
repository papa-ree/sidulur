<div class="space-y-4">
    <x-core::page-header
        :title="__('Pengajuan Selesai')"
        :subtitle="__('Pengajuan yang sudah diselesaikan petugas bidang.')">
        <x-slot name="action">
            <x-core::secondary-button link href="{{ route('sidulur.pelayanan.index') }}" label="{{ __('Kembali ke Pengajuan Masuk') }}" />
        </x-slot>
    </x-core::page-header>

    <livewire:core-shared-components::data-table
        model="Bale\Sidulur\Models\PelayananBidang"
        rowView="sidulur::livewire.pages.pelayanan.section.row"
        connectionResolver="Bale\Cms\Services\TenantConnectionService::resolveForQuery"
        :with="['pendaftaran.citizen', 'service', 'bidang']"
        queryUsing="Bale\Sidulur\Support\PelayananTable::selesai"
        searchUsing="Bale\Sidulur\Models\PelayananBidang::searchForTable"
        :columns="[
            ['key' => 'created_at', 'label' => __('Tiket'), 'sortable' => true],
            ['key' => 'bidang_id', 'label' => __('Layanan'), 'hidden' => 'lg'],
            ['key' => 'completed_at', 'label' => __('Selesai'), 'sortable' => true, 'hidden' => 'sm'],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ]"
        sortField="completed_at"
        sortDirection="desc"
        searchPlaceholder="{{ __('Cari tiket atau nama warga') }}" />
</div>
