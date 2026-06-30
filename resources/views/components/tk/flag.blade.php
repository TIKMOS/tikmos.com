@props([
    'code' => 'bj', // bj | fr
])

@php
    $cls = $attributes->get('class', 'h-3 w-[18px]');
@endphp

@if ($code === 'fr')
    <svg viewBox="0 0 3 2" {{ $attributes->merge(['class' => $cls.' inline-block rounded-[2px] align-middle ring-1 ring-black/10']) }} aria-hidden="true"><rect width="1" height="2" fill="#0055A4"/><rect width="1" height="2" x="1" fill="#fff"/><rect width="1" height="2" x="2" fill="#EF4135"/></svg>
@else
    <svg viewBox="0 0 5 3" {{ $attributes->merge(['class' => $cls.' inline-block rounded-[2px] align-middle ring-1 ring-black/10']) }} aria-hidden="true"><rect width="5" height="3" fill="#FCD116"/><rect y="1.5" width="5" height="1.5" fill="#E8112D"/><rect width="2" height="3" fill="#008751"/></svg>
@endif
