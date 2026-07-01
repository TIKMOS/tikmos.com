@props(['title' => null])

<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0b0b0c">
    @include('partials.pwa')
    <title>{{ $title ? $title.' — Tikmos' : 'Tikmos' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-dvh">
    <div class="grid min-h-dvh lg:grid-cols-2">
        {{-- Panneau de marque (desktop) — image + overlay --}}
        <div class="relative hidden flex-col justify-between overflow-hidden p-10 text-paper lg:flex">
            <img src="https://images.unsplash.com/photo-1549923746-c502d488b3ea?w=1100&q=70&auto=format&fit=crop"
                 alt="" class="absolute inset-0 h-full w-full object-cover" loading="eager" />
            <div class="absolute inset-0 bg-gradient-to-tr from-ink via-ink/85 to-ink/55"></div>

            <a href="{{ url('/') }}" wire:navigate class="relative"><x-tk.logo invert /></a>

            <div class="relative">
                <div class="mb-5 inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-xs font-medium backdrop-blur">
                    <svg viewBox="0 0 5 3" class="h-3 w-[18px] rounded-[2px]"><rect width="5" height="3" fill="#FCD116"/><rect y="1.5" width="5" height="1.5" fill="#E8112D"/><rect width="2" height="3" fill="#008751"/></svg>
                    Cotonou
                    <span class="text-white/40">↔</span>
                    <svg viewBox="0 0 3 2" class="h-3 w-[18px] rounded-[2px]"><rect width="1" height="2" fill="#0055A4"/><rect width="1" height="2" x="1" fill="#fff"/><rect width="1" height="2" x="2" fill="#EF4135"/></svg>
                    Paris
                </div>
                <h1 class="max-w-md font-display text-4xl font-black leading-[1.05]">Le pont de confiance entre la diaspora et chez soi.</h1>
                <p class="mt-4 max-w-sm text-white/70">Colis Paris ↔ Cotonou suivis à la trace, achat &amp; commande, mobilité.</p>
            </div>

            <p class="relative text-xs text-white/50">© {{ date('Y') }} Tikmos</p>
        </div>

        {{-- Formulaire --}}
        <div class="flex items-center justify-center px-6 py-10 sm:py-12">
            <div class="w-full max-w-sm">
                <div class="mb-8 lg:hidden">
                    <a href="{{ url('/') }}" wire:navigate><x-tk.logo /></a>
                </div>
                {{ $slot }}
            </div>
        </div>
    </div>

    <x-tk.toast />
    @livewireScripts
</body>
</html>
