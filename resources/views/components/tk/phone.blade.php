@props([
    'label' => null,
    'placeholder' => 'Numéro de téléphone',
    'invalid' => false,
])
@aware(['for' => null, 'hint' => null, 'error' => null, 'required' => false])

@php
    $invalid = $invalid || (bool) $error;
    $fieldId = $attributes->get('id') ?? $for;
    $descriptions = array_filter([$attributes->get('aria-describedby'), $for && $hint && ! $error ? $for.'-hint' : null, $for && $error ? $for.'-error' : null]);
    $describedBy = implode(' ', array_unique(preg_split('/\s+/', trim(implode(' ', $descriptions)), flags: PREG_SPLIT_NO_EMPTY)));
    $attributes = $attributes->except('aria-describedby')->merge([
        'aria-describedby' => $describedBy ?: null, 'aria-invalid' => $invalid ? 'true' : 'false',
        'required' => $required, 'aria-required' => $required ? 'true' : null,
    ]);
    $countries = config('tikmos.countries', []);
    $defaultCode = config('tikmos.default_country', 'bj');
    $default = collect($countries)->firstWhere('code', $defaultCode) ?? ($countries[0] ?? ['code' => 'bj', 'dial' => '+229']);
    $border = $invalid ? 'border-danger' : 'border-control-line';
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
        toggle() { this.open = !this.open; if (this.open) this.$nextTick(() => this.$refs.countries.querySelector('[aria-pressed=true]')?.focus()); },
        close() { this.open = false; this.$nextTick(() => this.$refs.dialToggle.focus()); },
        pick(code, dial) { this.code = code; this.dial = dial; this.open = false; this.sync(); this.$nextTick(() => this.$refs.number.focus()); },
    }"
    x-id="['phone-input', 'phone-countries']"
    @keydown.escape.stop.prevent="if (open) close()"
    @focusout="if (!$el.contains($event.relatedTarget)) open = false"
    class="relative"
>
    @if ($label)
        <label @if ($fieldId) for="{{ $fieldId }}" @else :for="$id('phone-input')" @endif class="mb-1.5 block text-[13px] font-medium text-content">{{ $label }}</label>
    @endif

    <div class="flex">
        <button type="button" x-ref="dialToggle" @click="toggle()"
                :aria-label="'Choisir l’indicatif téléphonique, actuellement ' + dial" :aria-expanded="open" :aria-controls="$id('phone-countries')"
                class="flex min-h-11 min-w-11 shrink-0 items-center gap-1.5 rounded-l-input border border-r-0 {{ $border }} bg-surface px-3 text-sm text-content transition-colors hover:bg-tk-50">
            <img :src="`https://flagcdn.com/${code}.svg`" alt="" class="h-3.5 w-5 rounded-[2px] object-cover ring-1 ring-black/5">
            <span x-text="dial" class="font-medium"></span>
            <x-lucide-chevron-down class="h-4 w-4 text-tk-400" />
        </button>
        <input type="tel" x-ref="number" x-model="number" inputmode="tel" placeholder="{{ $placeholder }}" @if ($fieldId) id="{{ $fieldId }}" @else :id="$id('phone-input')" @endif
               {{ $attributes->whereDoesntStartWith('wire:model')->except('id')->merge(['class' => 'min-h-[46px] min-w-0 w-full rounded-r-input border bg-surface px-4 text-[15px] text-content placeholder:text-muted focus:border-ink focus:ring-4 focus:ring-ink/10 '.$border]) }}>
    </div>

    <div x-ref="countries" :id="$id('phone-countries')" role="group" aria-label="Indicatifs téléphoniques" x-show="open" x-cloak x-transition.origin.top @click.outside="open = false"
         class="absolute z-30 mt-1 max-h-60 w-full min-w-[240px] overflow-auto rounded-card border border-line bg-surface p-1.5 shadow-pop">
        @foreach ($countries as $c)
            <button type="button" @click="pick(@js($c['code']), @js($c['dial']))"
                    :aria-pressed="code === @js($c['code'])" @keydown.down.prevent="$focus.within($refs.countries).wrap().next()" @keydown.up.prevent="$focus.within($refs.countries).wrap().previous()"
                    class="flex min-h-11 w-full items-center gap-2.5 rounded-tile px-3 py-2 text-sm hover:bg-tk-100"
                    :class="code === @js($c['code']) ? 'bg-tk-100' : ''">
                <img src="https://flagcdn.com/{{ $c['code'] }}.svg" alt="" class="h-3.5 w-5 rounded-[2px] object-cover ring-1 ring-black/5">
                <span class="text-content">{{ $c['name'] }}</span>
                <span class="ml-auto text-muted">{{ $c['dial'] }}</span>
            </button>
        @endforeach
    </div>
</div>
