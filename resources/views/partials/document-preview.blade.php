{{--
| Pratinjau dokumen tersimpan: thumbnail untuk gambar, kartu ikon untuk PDF.
| Parameter: $document (array{name,url,is_image}|null), $label (teks alternatif),
| $large (tampilkan gambar penuh selebar kartu; tetap di dalam batas induk).
--}}
@if (! empty($document))
    @if ($large ?? false)
        <div class="mt-2 overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/40">
            <a href="{{ $document['url'] }}" target="_blank" rel="noopener" class="block">
                @if ($document['is_image'])
                    <img src="{{ $document['url'] }}" alt="{{ $label ?? '' }}" loading="lazy"
                         class="mx-auto max-h-80 w-full bg-white object-contain dark:bg-gray-800">
                @else
                    <span class="flex h-40 flex-col items-center justify-center gap-2 text-rose-500 dark:text-rose-400">
                        <x-lucide-file-text class="size-10" />
                        <span class="text-xs font-medium">{{ __('Buka PDF') }}</span>
                    </span>
                @endif
            </a>

            <div class="flex items-center justify-between gap-2 border-t border-gray-200 px-3 py-2 dark:border-gray-700">
                <p class="min-w-0 flex-1 truncate text-xs font-medium text-gray-700 dark:text-gray-300">{{ $document['name'] }}</p>
                <a href="{{ $document['url'] }}" target="_blank" rel="noopener"
                   class="shrink-0 text-xs font-semibold text-purple-600 hover:underline dark:text-purple-400">
                    {{ __('Lihat') }}
                </a>
            </div>
        </div>
    @else
        <div class="mt-2 flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 p-2 dark:border-gray-700 dark:bg-gray-900/40">
            <a href="{{ $document['url'] }}" target="_blank" rel="noopener" tabindex="-1"
               class="shrink-0 overflow-hidden rounded-md border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                @if ($document['is_image'])
                    <img src="{{ $document['url'] }}" alt="{{ $label ?? '' }}" loading="lazy" class="h-14 w-20 object-cover">
                @else
                    <span class="flex h-14 w-20 items-center justify-center text-rose-500 dark:text-rose-400">
                        <x-lucide-file-text class="size-6" />
                    </span>
                @endif
            </a>

            <div class="min-w-0 flex-1">
                <p class="truncate text-xs font-medium text-gray-700 dark:text-gray-300">{{ $document['name'] }}</p>
                <p class="mt-0.5 text-[11px] text-gray-500 dark:text-gray-400">
                    {{ $document['is_image'] ? __('Gambar') : __('PDF') }} · {{ __('Tersimpan') }}
                </p>
            </div>

            <a href="{{ $document['url'] }}" target="_blank" rel="noopener"
               class="shrink-0 text-xs font-semibold text-purple-600 hover:underline dark:text-purple-400">
                {{ __('Lihat') }}
            </a>
        </div>
    @endif
@endif
