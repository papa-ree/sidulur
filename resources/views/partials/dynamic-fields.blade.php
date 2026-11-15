@foreach($fields as $field)
    @php
        $key = $field['key'];
        $model = $modelPrefix.'.'.$key;
        $options = collect($field['options'] ?? [])->map(fn ($option) => [
            'value' => (string) $option,
            'label' => (string) $option,
        ])->all();
    @endphp

    <div wire:key="{{ $modelPrefix }}-{{ $key }}">
        @if(($field['type'] ?? 'text') === 'textarea')
            <x-core::textarea :label="$field['label']" rows="3" wire:model="{{ $model }}" placeholder="{{ $field['placeholder'] ?? '' }}" />
        @elseif(($field['type'] ?? 'text') === 'select')
            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">{{ $field['label'] }}</label>
            <select wire:model="{{ $model }}"
                class="block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 focus:border-transparent focus:ring-2 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <option value="">{{ __('Pilih...') }}</option>
                @foreach($options as $option)
                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                @endforeach
            </select>
        @elseif(($field['type'] ?? 'text') === 'file')
            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">{{ $field['label'] }}</label>
            <input type="file" wire:model="{{ $model }}"
                class="block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-700 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
        @else
            <x-core::input :label="$field['label']" type="{{ $field['type'] ?? 'text' }}" wire:model="{{ $model }}" placeholder="{{ $field['placeholder'] ?? '' }}" />
        @endif

        @if($field['required'] ?? false)
            <p class="mt-1 text-[11px] text-gray-400 dark:text-gray-500">{{ __('Wajib diisi') }}</p>
        @endif
    </div>
@endforeach
