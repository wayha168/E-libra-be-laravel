@props(['value', 'prefix' => '#'])

@php
    $id = (string) $value;
    $compact = strtoupper(substr(str_replace('-', '', $id), 0, 8));
@endphp

<span
    {{ $attributes->merge(['class' => 'inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-mono bg-gray-100 text-gray-600 cursor-default']) }}
    title="{{ $id }}"
>{{ $prefix }}{{ $compact }}</span>
