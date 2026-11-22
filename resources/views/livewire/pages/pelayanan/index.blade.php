<div class="space-y-4">
    <x-core::page-header
        :title="__('Pengajuan Masuk')"
        :subtitle="__('Pengajuan dari pendaftaran yang belum selesai ditindaklanjuti bidang Anda.')">
        <x-slot name="action">
            <x-core::secondary-button link href="{{ route('sidulur.pelayanan.selesai') }}" label="{{ __('Pengajuan Selesai') }}" />
        </x-slot>
    </x-core::page-header>

    <livewire:core-shared-components::data-table
        model="Bale\Sidulur\Models\PelayananBidang"
        rowView="sidulur::livewire.pages.pelayanan.section.row"
        connectionResolver="Bale\Cms\Services\TenantConnectionService::resolveForQuery"
        :with="['pendaftaran.citizen', 'service', 'bidang']"
        queryUsing="Bale\Sidulur\Support\PelayananTable::pelayanan"
        searchUsing="Bale\Sidulur\Models\PelayananBidang::searchForTable"
        :columns="[
            ['key' => 'created_at', 'label' => __('Tiket'), 'sortable' => true],
            ['key' => 'bidang_id', 'label' => __('Layanan'), 'hidden' => 'lg'],
            ['key' => 'status', 'label' => __('Status'), 'hidden' => 'sm'],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ]"
        :filterOptions="[
            ['key' => 'status', 'label' => __('Status'), 'options' => [
                'baru' => __('Baru'),
                'verlap_selesai' => __('Verlap selesai'),
                'dijadwalkan' => __('Dijadwalkan'),
            ]],
        ]"
        searchPlaceholder="{{ __('Cari tiket atau nama warga') }}" />
</div>
