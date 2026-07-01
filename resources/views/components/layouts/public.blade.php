@props(['title' => null])

<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#17306e">
    @include('partials.pwa')
    {{ $head ?? '' }}
    @stack('meta')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
    @php
        $jsonLd = json_encode([
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Person',
                    'name' => $seo->person_name,
                    'jobTitle' => $seo->person_job_title,
                    'url' => $seo->person_url ?: url('/'),
                    'worksFor' => ['@type' => 'Organization', 'name' => $seo->organization_name],
                ],
                [
                    '@type' => 'ProfessionalService',
                    'name' => $seo->organization_name,
                    'description' => $seo->default_description,
                    'url' => url('/'),
                    'telephone' => $general->brand_phone,
                    'email' => $general->emails['contact'] ?? null,
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    @endphp
    <script type="application/ld+json">{!! $jsonLd !!}</script>
</head>
<body class="flex min-h-dvh flex-col">
    {{-- Bandeau baseline --}}
    @if ($general->top_bandeau_enabled)
    <div class="bg-ink text-paper">
        <div class="mx-auto flex h-9 w-full max-w-6xl items-center justify-center gap-3 px-5 text-xs sm:justify-between sm:px-6 lg:px-8">
            <p class="flex items-center gap-2 text-tk-300">
                <span class="h-1.5 w-1.5 rounded-full bg-gold"></span>
                <span>{{ $general->top_bandeau_text }}</span>
            </p>
            <a href="https://wa.me/{{ $waDigits }}" target="_blank" rel="noopener" class="hidden items-center gap-1.5 text-tk-300 transition-colors hover:text-white sm:flex">
                <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="currentColor" aria-hidden="true"><path d="M.057 24l1.687-6.163a11.867 11.867 0 0 1-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.821 11.821 0 0 1 8.413 3.488 11.824 11.824 0 0 1 3.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 0 1-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01a1.092 1.092 0 0 0-.792.372c-.272.297-1.04 1.016-1.04 2.479s1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                {{ $general->brand_phone }}
            </a>
        </div>
    </div>
    @endif

    {{-- En-tête --}}
    <header
        x-data="{ scrolled: false, open: false }"
        x-on:scroll.window="scrolled = window.scrollY > 8"
        class="sticky top-0 z-30 transition-colors duration-300"
        :class="scrolled ? 'border-b border-line bg-surface/85 backdrop-blur' : ''"
    >
        <div class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-5 sm:px-6 lg:px-8">
            <a href="{{ url('/') }}" wire:navigate aria-label="Accueil Tikmos">
                <x-tk.logo class="h-12 md:h-14" />
            </a>

            @php
                $navLinks = collect($general->nav_links)->filter(fn ($l) => $l['visible'] ?? true)->values()->all();
            @endphp

            <nav class="hidden items-center gap-1 text-sm font-medium md:flex">
                @foreach ($navLinks as $link)
                    @php($active = request()->is($link['path']) || request()->is($link['path'].'/*'))
                    <a href="{{ url('/'.$link['path']) }}" wire:navigate @if ($active) aria-current="page" @endif
                       class="rounded-btn px-3 py-2 transition-colors {{ $active ? 'bg-tk-100 text-content' : 'text-content hover:bg-tk-50' }}">{{ $link['label'] }}</a>
                @endforeach
                @if ($shopEnabled ?? false)
                    @php($shopActive = request()->is('boutique') || request()->is('boutique/*'))
                    <a href="{{ url('/boutique') }}" wire:navigate class="rounded-btn px-3 py-2 transition-colors {{ $shopActive ? 'bg-tk-100 text-content' : 'text-content hover:bg-tk-50' }}">Boutique</a>
                @endif
            </nav>

            <div class="flex items-center gap-2">
                @if ($shopEnabled ?? false)<livewire:cart-icon />@endif
                <div class="hidden items-center gap-2 sm:flex">
                    @guest
                        <x-tk.button href="{{ route('login') }}" variant="secondary" size="sm">Connexion</x-tk.button>
                        <x-tk.button href="{{ url('/contact') }}" variant="primary" size="sm" icon="file-text">Demander un devis</x-tk.button>
                    @else
                        <div class="relative" x-data="{ acc: false }">
                            <button @click="acc = !acc" class="flex items-center gap-2 rounded-pill py-1 pl-1 pr-2.5 transition-colors hover:bg-tk-100" aria-label="Mon compte">
                                <x-tk.avatar :name="auth()->user()->name" size="sm" />
                                <x-lucide-chevron-down class="h-4 w-4 text-tk-500" />
                            </button>
                            <div x-show="acc" x-cloak @click.outside="acc = false" x-transition.origin.top.right
                                 class="absolute right-0 top-12 z-40 w-52 rounded-card border border-line bg-surface p-1.5 shadow-pop">
                                <p class="truncate px-3 py-1.5 text-xs text-muted">{{ auth()->user()->name }}</p>
                                <a href="{{ auth()->user()->homeRoute() }}" wire:navigate class="flex items-center gap-2 rounded-tile px-3 py-2 text-sm text-content hover:bg-tk-100"><x-lucide-layout-dashboard class="h-4 w-4" /> Mon espace</a>
                                @unless (auth()->user()->isStaff() || auth()->user()->isLivreur())
                                    <a href="{{ url('/app/compte') }}" wire:navigate class="flex items-center gap-2 rounded-tile px-3 py-2 text-sm text-content hover:bg-tk-100"><x-lucide-user class="h-4 w-4" /> Mon compte</a>
                                @endunless
                                <form method="POST" action="{{ route('logout') }}" class="border-t border-line pt-1 mt-1">@csrf
                                    <button type="submit" class="flex w-full items-center gap-2 rounded-tile px-3 py-2 text-sm text-danger hover:bg-danger-soft"><x-lucide-log-out class="h-4 w-4" /> Déconnexion</button>
                                </form>
                            </div>
                        </div>
                    @endguest
                </div>
                <button class="grid h-10 w-10 place-items-center rounded-tile text-content md:hidden" @click="open = !open" aria-label="Menu">
                    <x-lucide-menu x-show="!open" class="h-6 w-6" />
                    <x-lucide-x x-show="open" x-cloak class="h-6 w-6" />
                </button>
            </div>
        </div>

        {{-- Menu mobile --}}
        <div x-show="open" x-cloak x-collapse class="border-t border-line bg-surface md:hidden">
            <nav class="flex flex-col gap-1 px-4 py-3">
                @foreach ($navLinks as $link)
                    @php($active = request()->is($link['path']) || request()->is($link['path'].'/*'))
                    <a href="{{ url('/'.$link['path']) }}" wire:navigate
                       class="rounded-btn px-3 py-2.5 text-sm font-medium {{ $active ? 'bg-tk-100 text-content' : 'text-content hover:bg-tk-50' }}">{{ $link['label'] }}</a>
                @endforeach
                @if ($shopEnabled ?? false)
                    <a href="{{ url('/boutique') }}" wire:navigate class="rounded-btn px-3 py-2.5 text-sm font-medium text-content hover:bg-tk-50">Boutique</a>
                @endif
                @auth
                    <a href="{{ auth()->user()->homeRoute() }}" wire:navigate class="rounded-btn px-3 py-2.5 text-sm font-medium text-content hover:bg-tk-50">Mon espace</a>
                @endauth
                <div class="mt-2 flex gap-2 border-t border-line pt-3">
                    @guest
                        <x-tk.button href="{{ route('login') }}" variant="secondary" size="sm" class="flex-1">Connexion</x-tk.button>
                        <x-tk.button href="{{ url('/contact') }}" variant="primary" size="sm" icon="file-text" class="flex-1">Devis</x-tk.button>
                    @else
                        <form method="POST" action="{{ route('logout') }}" class="flex-1">@csrf
                            <x-tk.button type="submit" variant="primary" size="sm" icon="log-out" class="w-full">Déconnexion</x-tk.button>
                        </form>
                    @endguest
                </div>
            </nav>
        </div>
    </header>

    <main>
        {{ $slot }}
    </main>

    {{-- Zone partenaires (avant le footer) --}}
    @if (($footerPartners ?? collect())->isNotEmpty())
        <section class="border-t border-line bg-canvas py-12">
            <div class="mx-auto max-w-6xl px-5 sm:px-6 lg:px-8">
                <p class="text-center text-xs font-semibold uppercase tracking-[0.18em] text-tk-400">Ils nous font confiance</p>
                <div class="mt-7 flex flex-wrap items-center justify-center gap-x-10 gap-y-6">
                    @foreach ($footerPartners as $partner)
                        @php($logo = $partner->logoUrl())
                        <a @if ($partner->url) href="{{ $partner->url }}" target="_blank" rel="noopener" @endif
                           class="group grayscale transition hover:grayscale-0" title="{{ $partner->name }}">
                            @if ($logo)
                                <img src="{{ $logo }}" alt="{{ $partner->name }}" class="h-9 w-auto opacity-70 transition group-hover:opacity-100">
                            @else
                                <span class="font-display text-xl font-bold text-tk-400 transition group-hover:text-content">{{ $partner->name }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Pied de page --}}
    <footer class="bg-ink text-paper">
        <div class="mx-auto grid w-full max-w-6xl gap-10 px-5 py-16 sm:px-6 md:grid-cols-12 lg:px-8">
            <div class="md:col-span-4">
                <x-tk.logo invert />
                <p class="mt-4 max-w-xs text-sm leading-relaxed text-tk-400">{{ $general->footer_tagline }}</p>
                <div class="mt-5 flex items-center gap-2.5">
                    @foreach ($general->socials as $social)
                        @continue(empty($social['url']) || $social['url'] === '#')
                        <a href="{{ $social['url'] }}" target="_blank" rel="noopener" aria-label="{{ $social['network'] }}"
                           class="grid h-9 w-9 place-items-center rounded-full border border-white/15 text-tk-300 transition-colors hover:border-white/40 hover:text-white">
                            <x-dynamic-component :component="'lucide-'.$social['network']" class="h-4 w-4" />
                        </a>
                    @endforeach
                    <a href="https://wa.me/{{ $waDigits }}" target="_blank" rel="noopener" aria-label="WhatsApp"
                       class="grid h-9 w-9 place-items-center rounded-full border border-white/15 text-tk-300 transition-colors hover:border-white/40 hover:text-white">
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor" aria-hidden="true"><path d="M.057 24l1.687-6.163a11.867 11.867 0 0 1-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.821 11.821 0 0 1 8.413 3.488 11.824 11.824 0 0 1 3.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 0 1-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01a1.092 1.092 0 0 0-.792.372c-.272.297-1.04 1.016-1.04 2.479s1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                    </a>
                </div>
            </div>

            <div class="md:col-span-3">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-tk-500">Liens rapides</p>
                <ul class="mt-4 space-y-2.5 text-sm text-tk-400">
                    <li><a href="{{ url('/a-propos') }}" wire:navigate class="hover:text-white">À propos</a></li>
                    <li><a href="{{ url('/services') }}" wire:navigate class="hover:text-white">Services</a></li>
                    <li><a href="{{ url('/blog') }}" wire:navigate class="hover:text-white">Blog</a></li>
                    <li><a href="{{ url('/boutique') }}" wire:navigate class="hover:text-white">Boutique</a></li>
                </ul>
            </div>

            <div class="md:col-span-2">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-tk-500">Ressources</p>
                <ul class="mt-4 space-y-2.5 text-sm text-tk-400">
                    <li><a href="{{ url('/cgu') }}" wire:navigate class="hover:text-white">CGU</a></li>
                    <li><a href="{{ url('/cgv') }}" wire:navigate class="hover:text-white">CGV (boutique)</a></li>
                    <li><a href="{{ url('/confidentialite') }}" wire:navigate class="hover:text-white">Confidentialité</a></li>
                    <li><a href="{{ url('/cookies') }}" wire:navigate class="hover:text-white">Cookies</a></li>
                    <li><button type="button" x-data @click="window.dispatchEvent(new CustomEvent('sz-open-cookie-prefs'))" class="hover:text-white">Gérer les cookies</button></li>
                </ul>
            </div>

            <div class="md:col-span-3">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-tk-500">Contact</p>
                <ul class="mt-4 space-y-2.5 text-sm text-tk-400">
                    <li><a href="https://wa.me/{{ $waDigits }}" class="hover:text-white">{{ $general->brand_phone }}</a></li>
                    @if (! empty($general->emails['contact']))
                        <li><a href="mailto:{{ $general->emails['contact'] }}" class="hover:text-white">{{ $general->emails['contact'] }}</a></li>
                    @endif
                </ul>
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="mx-auto flex w-full max-w-6xl flex-col gap-2 px-5 py-5 text-xs text-tk-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                <p>© {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <x-tk.whatsapp-button />
    <x-tk.toast />
    <x-tk.cookie-consent />

    @stack('scripts')
    @livewireScripts
</body>
</html>
