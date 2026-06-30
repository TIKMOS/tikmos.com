@props([
    'label' => null,
    'placeholder' => 'Numéro de téléphone',
    'invalid' => false,
])

@php
    $countries = config('tikmos.countries', []);
    $defaultCode = config('tikmos.default_country', 'bj');
    $default = collect($countries)->firstWhere('code', $defaultCode) ?? ($countries[0] ?? ['code' => 'bj', 'dial' => '+229']);
    $border = $invalid ? 'border-danger' : 'border-tk-300';
@endphp

<div
    x-data="{
        open: false,
        code: @js($default['code']),
        dial: @js($default['dial']),
        number: '',
        model: @entangle($attributes->wire('model')),
        dials: @js(collect($countries)->pluck('dial')->unique()->values()->all()),
        init() {
            const v = (this.model || '').toString().trim();
            if (v) {
                const found = this.dials.sort((a, b) => b.length - a.length).find((d) => v.startsWith(d));
                if (found) { this.dial = found; this.number = v.slice(found.length).trim(); }
                else { this.number = v; }
            }
            this.$watch('number', () => this.sync());
        },
        sync() { this.model = this.number ? (this.dial + ' ' + this.number.trim()) : ''; },
        pick(code, dial) { this.code = code; this.dial = dial; this.open = false; this.sync(); },
    }"
    class="relative"
>
    @if ($label)
        <label class="mb-1.5 block text-[13px] font-medium text-content">{{ $label }}</label>
    @endif

    <div class="flex">
        <button type="button" @click="open = !open"
                class="flex shrink-0 items-center gap-1.5 rounded-l-input border border-r-0 {{ $border }} bg-surface px-3 text-sm text-content transition-colors hover:bg-tk-50 focus:outline-none">
            <img :src="`https://flagcdn.com/${code}.svg`" alt="" class="h-3.5 w-5 rounded-[2px] object-cover ring-1 ring-black/5">
            <span x-text="dial" class="font-medium"></span>
            <x-lucide-chevron-down class="h-4 w-4 text-tk-400" />
        </button>
        <input type="tel" x-model="number" inputmode="tel" placeholder="{{ $placeholder }}"
               {{ $attributes->whereDoesntStartWith('wire:model')->merge(['class' => 'min-h-[46px] w-full rounded-r-input border bg-surface px-4 text-[15px] text-content placeholder:text-tk-400 focus:border-ink focus:outline-none focus:ring-4 focus:ring-ink/10 '.$border]) }}>
    </div>

    <div x-show="open" x-cloak x-transition.origin.top @click.outside="open = false"
         class="absolute z-30 mt-1 max-h-60 w-full min-w-[240px] overflow-auto rounded-card border border-line bg-surface p-1.5 shadow-pop">
        @foreach ($countries as $c)
            <button type="button" @click="pick(@js($c['code']), @js($c['dial']))"
                    class="flex w-full items-center gap-2.5 rounded-tile px-3 py-2 text-sm hover:bg-tk-100"
                    :class="code === @js($c['code']) ? 'bg-tk-100' : ''">
                <img src="https://flagcdn.com/{{ $c['code'] }}.svg" alt="" class="h-3.5 w-5 rounded-[2px] object-cover ring-1 ring-black/5">
                <span class="text-content">{{ $c['name'] }}</span>
                <span class="ml-auto text-muted">{{ $c['dial'] }}</span>
            </button>
        @endforeach
    </div>
</div>
