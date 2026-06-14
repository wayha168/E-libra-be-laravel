@props([
    'action',
    'placeholder' => 'Search…',
    'submitLabel' => 'Search',
    'searchName' => 'search',
    'filters' => [],
    'preserve' => [],
    'manual' => false,
])

@php
    $queryKeys = array_merge([$searchName], collect($filters)->pluck('name')->all());
    $hasActiveFilters = collect($queryKeys)->contains(fn ($key) => filled(request($key)));
    $clearQuery = collect($preserve)->filter(fn ($value) => filled($value))->all();
    $clearUrl = $clearQuery ? ($action . '?' . http_build_query($clearQuery)) : $action;
@endphp

<form
    method="GET"
    action="{{ $action }}"
    class="flex flex-wrap gap-2 items-center"
    @unless($manual) data-auto-search @endunless
>
    @foreach($preserve as $name => $value)
        @if(filled($value))
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endif
    @endforeach

    <input
        type="search"
        name="{{ $searchName }}"
        value="{{ request($searchName) }}"
        placeholder="{{ $placeholder }}"
        class="border border-gray-300 rounded-lg px-3 py-2 text-sm min-w-[12rem]"
        autocomplete="off"
        @unless($manual) data-auto-search-input @endunless
    />

    @foreach($filters as $filter)
        <select
            name="{{ $filter['name'] }}"
            class="border border-gray-300 rounded-lg px-3 py-2 text-sm"
            @unless($manual) data-auto-search-select @endunless
        >
            @foreach($filter['options'] as $value => $label)
                <option value="{{ $value }}" @selected((string) request($filter['name']) === (string) $value)>{{ $label }}</option>
            @endforeach
        </select>
    @endforeach

    @if($manual)
        <button type="submit" class="px-3 py-2 bg-black text-white rounded-lg text-sm hover:bg-gray-800 transition">
            {{ $submitLabel }}
        </button>
    @else
        <button type="submit" class="sr-only">{{ $submitLabel }}</button>
    @endif

    @if($hasActiveFilters)
        <a href="{{ $clearUrl }}" class="px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition">
            Clear
        </a>
    @endif
</form>
