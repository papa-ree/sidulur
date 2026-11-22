{{--
| Dokumen hasil layanan.
| Parameter: $pelayanan, $canUpload (bool)
--}}
@php
    $canUpload = $canUpload ?? false;
@endphp

<div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Dokumen Hasil Layanan') }}</h2>
    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Opsional. Berkas hasil layanan yang nanti diunduh pemohon.') }}</p>

    @if($canUpload)
        <form wire:submit="uploadHasil" class="mt-3">
            <x-core::upload-zone
                wire:model="hasil"
                accept=".jpg,.jpeg,.png,.pdf"
                :maxSize="4096"
                :label="__('Klik atau seret berkas ke sini')"
                :hint="__('JPG, PNG, PDF · maks 4 MB')" />
            @error('hasil') <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror

            <div class="mt-3">
                <x-core::textarea label="{{ __('Catatan penggantian') }}" wire:model="hasil_note" rows="2" />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Wajib diisi bila mengganti dokumen hasil yang sudah ada. Berkas lama akan dihapus.') }}</p>
                @error('hasil_note') <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
            </div>

            <div class="mt-3">
                <x-core::button type="submit" :label="__('Unggah')" />
            </div>
        </form>
    @endif

    <div class="mt-3 space-y-2">
        @forelse($pelayanan->pendaftaran?->documents?->where('kind', 'hasil') ?? [] as $document)
            @include('sidulur::partials.document-preview', [
                'document' => app(\Bale\Sidulur\Services\DocumentService::class)->preview($document),
                'label' => __('Dokumen hasil layanan'),
            ])
        @empty
            <p class="text-xs italic text-gray-500 dark:text-gray-400">{{ __('Belum ada dokumen hasil.') }}</p>
        @endforelse
    </div>

    @if($pelayanan->form_data['_hasil_notes'] ?? [])
        <div class="mt-4 border-t border-gray-100 pt-3 dark:border-gray-700">
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('Riwayat penggantian') }}</p>
            <div class="mt-1 space-y-1">
                @foreach(array_reverse($pelayanan->form_data['_hasil_notes']) as $revision)
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $revision['at'] }} — {{ $revision['note'] }}</p>
                @endforeach
            </div>
        </div>
    @endif
</div>
