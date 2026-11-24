{{--
| Pemilih media dari percakapan chat aktif.
| Parameter: $kode, $mediaMessages, $mediaModel, $mediaSelected
--}}
@php
    $selected = ($mediaMessages ?? collect())->firstWhere('id', $mediaSelected ?? null);
@endphp

<div class="mt-2" x-data="{ picker: false }">
    <button type="button"
            x-on:click="picker = !picker"
            class="inline-flex items-center gap-1 text-[11px] font-medium text-purple-600 hover:underline dark:text-purple-400">
        <x-lucide-paperclip class="size-3" aria-hidden="true" />
        {{ __('Ambil dari chat') }}
    </button>

    @if($selected)
        <div class="mt-1.5 flex items-center gap-2 rounded-md border border-teal-100 bg-teal-50/60 px-2 py-1 text-[11px] text-teal-800 dark:border-teal-800/40 dark:bg-teal-900/20 dark:text-teal-300">
            <span class="min-w-0 flex-1 truncate">{{ $selected->media_name ?: __('Media chat') }}</span>
            <button type="button"
                    wire:click="$set('{{ $mediaModel }}.{{ $kode }}', null)"
                    class="shrink-0 font-semibold hover:underline">
                {{ __('Batal') }}
            </button>
        </div>
    @endif

    <div x-show="picker"
         x-cloak
         class="mt-1.5 grid max-h-44 grid-cols-2 gap-1.5 overflow-y-auto overscroll-contain rounded-lg border border-gray-200 p-1.5 dark:border-gray-700">
        @forelse($mediaMessages ?? [] as $media)
            @php $url = \Bale\Vada\Support\Media::url($media); @endphp

            <button type="button"
                    wire:key="chat-media-{{ $media->getKey() }}"
                    wire:click="$set('{{ $mediaModel }}.{{ $kode }}', '{{ $media->getKey() }}')"
                    class="rounded-md border px-1.5 py-1.5 text-left transition-colors hover:border-purple-300 hover:bg-purple-50/50 dark:hover:border-purple-700 dark:hover:bg-purple-950/20 {{ $mediaSelected === $media->getKey() ? 'border-purple-400 bg-purple-50 dark:border-purple-600 dark:bg-purple-950/30' : 'border-gray-200 dark:border-gray-700' }}">
                @if($url && $media->isImage())
                    <img src="{{ $url }}" alt="{{ $media->media_name }}" class="h-10 w-full rounded object-cover" loading="lazy" />
                @else
                    <x-lucide-file-text class="mx-auto size-5 text-gray-400 dark:text-gray-500" aria-hidden="true" />
                @endif

                <span class="mt-1 block truncate text-[10px] text-gray-600 dark:text-gray-300">
                    {{ $media->media_name ?: $media->mediaLabel() }}
                </span>
            </button>
        @empty
            <p class="col-span-2 py-2 text-center text-[11px] text-gray-400 dark:text-gray-500">{{ __('Belum ada media hari ini.') }}</p>
        @endforelse
    </div>
</div>
