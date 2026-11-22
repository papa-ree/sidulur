{{--
| Persyaratan tambahan formulir layanan (di luar identitas & berkas warga).
| Parameter input:   $requirements, $dataModel, $filesModel
| Parameter baca:    $requirements, $values, $documents
| Parameter periksa: $inspect (checkbox petugas bidang)
| Parameter media:   $mediaPicker, $mediaMessages, $mediaModel, $mediaSelected
--}}
@php
    $editable = isset($dataModel) && isset($filesModel);
    $values = $values ?? [];
    $documents = $documents ?? collect();
    $inspect = $inspect ?? false;
@endphp

<div class="space-y-3">
    @forelse($requirements as $row)
        @php
            $kode = $row['key'];
            $type = $row['type'] ?? 'file';
            $wajib = (bool) ($row['required'] ?? false);
            $berkas = $documents->where('kind', $kode);
        @endphp

        <div wire:key="syarat-{{ $kode }}" class="rounded-lg border border-gray-100 px-3 py-3 dark:border-gray-700">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
                        {{ $row['label'] }}{{ $wajib ? ' *' : '' }}
                    </p>
                    @if(! empty($row['keterangan']))
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $row['keterangan'] }}</p>
                    @endif
                </div>
            </div>

            @if($editable)
                <div class="mt-2">
                    @if($type === 'file')
                        <x-core::upload-zone
                            wire:model="{{ $filesModel }}.{{ $kode }}"
                            accept=".jpg,.jpeg,.png,.webp,.pdf"
                            maxSize="4096"
                            :maxFiles="3"
                            multiple
                            removeMethod="removeRequirementFile"
                            :removeParam="$kode"
                            :label="__('Klik atau seret berkas ke sini')"
                            :hint="__('JPG, PNG, WebP, PDF · maks 3 berkas · 4 MB per berkas')" />
                        <x-core::input-error :for="$filesModel.'.'.$kode" class="mt-1.5" />
                        @for($i = 0; $i < 3; $i++)
                            <x-core::input-error :for="$filesModel.'.'.$kode.'.'.$i" class="mt-1" />
                        @endfor

                        @if($mediaPicker ?? false)
                            @include('sidulur::partials.media-picker', [
                                'kode' => $kode,
                                'mediaMessages' => $mediaMessages ?? collect(),
                                'mediaModel' => $mediaModel,
                                'mediaSelected' => $mediaSelected[$kode] ?? null,
                            ])
                        @endif
                    @elseif($type === 'textarea')
                        <x-core::textarea rows="3" wire:model="{{ $dataModel }}.{{ $kode }}" />
                    @elseif($type === 'date')
                        <x-core::date-picker mode="date" :model="$dataModel.'.'.$kode" />
                    @elseif($type === 'number')
                        {{-- shortcut: mask digit saja, upgrade bila butuh desimal. --}}
                        <x-core::input type="text" inputmode="numeric" x-mask="999999999999" wire:model="{{ $dataModel }}.{{ $kode }}" />
                    @else
                        <x-core::input type="{{ $type }}" wire:model="{{ $dataModel }}.{{ $kode }}" />
                    @endif

                    @error($dataModel.'.'.$kode) <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                </div>
            @else
                <div class="mt-2 space-y-1">
                    @if($type === 'file')
                        @forelse($berkas as $document)
                            @include('sidulur::partials.document-preview', [
                                'document' => app(\Bale\Sidulur\Services\DocumentService::class)->preview($document),
                                'label' => $row['label'],
                                'large' => true,
                            ])
                        @empty
                            <p class="text-xs italic text-gray-400 dark:text-gray-500">{{ __('Belum ada berkas.') }}</p>
                        @endforelse
                    @else
                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ filled($values[$kode] ?? null) ? $values[$kode] : '—' }}</p>
                    @endif
                </div>
            @endif

            @if($inspect && $wajib)
                <label class="mt-2 flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                    <x-core::checkbox wire:model="requirements.{{ $kode }}" /> {{ __('Sudah diperiksa') }}
                </label>
                @error('requirements.'.$kode) <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
            @endif
        </div>
    @empty
        <p class="text-xs italic text-gray-400 dark:text-gray-500">{{ __('Layanan ini tidak memiliki persyaratan tambahan.') }}</p>
    @endforelse
</div>
