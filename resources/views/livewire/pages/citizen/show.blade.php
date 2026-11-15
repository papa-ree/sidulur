@php
    $card = $citizen->familyCard;
    $tone = fn (string $status) => match ($status) {
        'selesai' => 'bg-teal-50 text-teal-700 border-teal-100 dark:bg-teal-900/20 dark:text-teal-300 dark:border-teal-800/40',
        default => 'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800/40',
    };
@endphp

<div class="space-y-4">
    <x-core::page-header :title="$citizen->nama" :subtitle="__('NIK ').$citizen->maskedNik()">
        <x-slot name="action">
            <div class="flex gap-2">
                @can('sidulur.citizen.update')
                    <x-core::secondary-button link href="{{ route('sidulur.citizen.form', $citizen->getKey()) }}" label="{{ __('Ubah') }}" />
                @endcan
                @can('sidulur.pendaftaran.create')
                    <x-core::button link href="{{ route('sidulur.pendaftaran.create', ['citizen' => $citizen->getKey()]) }}" label="{{ __('Buat Pengajuan') }}" />
                @endcan
            </div>
        </x-slot>
    </x-core::page-header>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Identitas') }}</h2>
                <dl class="mt-3 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Nama') }}</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $citizen->nama }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('NIK') }}</dt><dd class="font-mono text-gray-900 dark:text-white">{{ $citizen->nik }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Nomor HP') }}</dt><dd class="text-gray-900 dark:text-white">{{ $citizen->phone ?: '—' }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Status') }}</dt><dd class="text-gray-900 dark:text-white">{{ $citizen->is_active ? __('Aktif') : __('Nonaktif') }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-gray-500 dark:text-gray-400">{{ __('Alamat') }}</dt><dd class="text-gray-900 dark:text-white">{{ $card?->alamat }}, {{ $card?->village_name }}, {{ $card?->district_name }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ __('Nomor KK') }}</dt><dd class="font-mono text-gray-900 dark:text-white">{{ $card?->nomor_kk }}</dd></div>
                </dl>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Anggota Keluarga') }}</h2>
                </div>
                <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($card?->citizens ?? [] as $member)
                        <li class="flex items-center justify-between gap-3 px-5 py-3">
                            <div>
                                <a href="{{ route('sidulur.citizen.show', $member->getKey()) }}" wire:navigate class="text-sm font-medium text-gray-900 hover:text-purple-600 dark:text-white dark:hover:text-purple-400">{{ $member->nama }}</a>
                                <p class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $member->maskedNik() }}</p>
                            </div>
                            @if($member->getKey() === $citizen->getKey())
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500 dark:bg-gray-700 dark:text-gray-300">{{ __('Saat ini') }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Riwayat Pengajuan') }}</h2>
                </div>
                <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($citizen->pendaftarans as $item)
                        <li class="flex items-center justify-between gap-3 px-5 py-3">
                            <div>
                                <a href="{{ route('sidulur.pendaftaran.show', $item->getKey()) }}" wire:navigate class="font-mono text-sm text-purple-600 hover:underline dark:text-purple-400">{{ $item->ticket_number }}</a>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $item->service?->nama }} · {{ $item->created_at?->format('d M Y') }}</p>
                            </div>
                            <span class="rounded-md border px-2 py-0.5 text-xs font-medium {{ $tone($item->status) }}">{{ str($item->status)->title() }}</span>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('Belum ada pengajuan.') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Dokumen') }}</h2>
            <ul class="mt-3 space-y-2 text-sm">
                @forelse($citizen->documents as $document)
                    <li class="flex items-center justify-between rounded-lg border border-gray-100 px-3 py-2 dark:border-gray-700">
                        <span class="uppercase text-xs font-semibold text-gray-600 dark:text-gray-300">{{ $document->kind }}</span>
                        <a href="{{ route('sidulur.media', $document->getKey()) }}" class="text-xs font-medium text-purple-600 hover:underline dark:text-purple-400">{{ __('Unduh') }}</a>
                    </li>
                @empty
                    <li class="text-gray-500 dark:text-gray-400">{{ __('Belum ada dokumen warga.') }}</li>
                @endforelse
                @foreach($card?->documents ?? [] as $document)
                    <li class="flex items-center justify-between rounded-lg border border-gray-100 px-3 py-2 dark:border-gray-700">
                        <span class="uppercase text-xs font-semibold text-gray-600 dark:text-gray-300">KK</span>
                        <a href="{{ route('sidulur.media', $document->getKey()) }}" class="text-xs font-medium text-purple-600 hover:underline dark:text-purple-400">{{ __('Unduh') }}</a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
