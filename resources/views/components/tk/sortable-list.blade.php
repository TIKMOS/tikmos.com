@props([
    'onReorder' => 'reorder', // méthode Livewire appelée avec la liste ordonnée des data-id
])

{{--
    Liste réordonnable. Chaque élément enfant doit porter `data-id="{{ $item->id }}"`
    et `wire:key`. Glisser-déposer → appelle `$onReorder(ids)`.
--}}
<ul x-data="szSortable(@js($onReorder))" {{ $attributes->merge(['class' => 'space-y-2']) }}>
    {{ $slot }}
</ul>
