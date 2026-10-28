@props([
    'member', // App\Models\TeamMember
    'tint' => 'bg-mist',
])

<div {{ $attributes->merge(['class' => 'group flex flex-col overflow-hidden rounded-card border border-line/60 bg-surface transition-all hover:-translate-y-1 hover:shadow-pop']) }}>
    <div class="relative aspect-[4/5] overflow-hidden {{ $tint }}">
        @if ($member->photoUrl())
            <img src="{{ $member->photoUrl() }}" alt="{{ $member->name }}" loading="lazy"
                 class="h-full w-full object-cover grayscale transition-all duration-500 group-hover:grayscale-0 group-hover:scale-[1.03]">
        @else
            <div class="grid h-full place-items-center">
                <span class="font-display text-6xl font-black text-content/20">{{ \Illuminate\Support\Str::of($member->name)->explode(' ')->map(fn ($p) => \Illuminate\Support\Str::substr($p, 0, 1))->take(2)->implode('') }}</span>
            </div>
        @endif
        @if ($member->is_featured)
            <span class="absolute left-3 top-3 rounded-pill bg-ink px-3 py-1 text-xs font-semibold text-paper">Fondateur</span>
        @endif
    </div>
    <div class="flex flex-1 flex-col p-5">
        <h2 class="font-display text-xl font-bold text-content">{{ $member->name }}</h2>
        <p class="text-sm font-medium text-gold-ink">{{ $member->role_title }}</p>
        @if ($member->bio)<p class="mt-3 text-sm leading-relaxed text-content/75">{{ $member->bio }}</p>@endif

        @if (! empty($member->funfacts))
            <div class="mt-4 flex flex-wrap gap-1.5">
                @foreach ($member->funfacts as $fact)
                    <span class="rounded-pill bg-canvas px-2.5 py-1 text-xs text-content">{{ $fact }}</span>
                @endforeach
            </div>
        @endif

        @if (! empty($member->socials))
            <div class="mt-4 flex items-center gap-2 border-t border-line pt-4">
                @foreach ($member->socials as $social)
                    @continue(empty($social['url']))
                    <a href="{{ $social['url'] }}" target="_blank" rel="noopener" aria-label="{{ $social['network'] }}"
                       class="grid h-8 w-8 place-items-center rounded-full border border-line text-tk-500 transition-colors hover:border-ink hover:text-content">
                        <x-dynamic-component :component="'lucide-'.$social['network']" class="h-4 w-4" />
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
