<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0b0b0c">
    @include('partials.pwa')
    <title>Livreur — Tikmos</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-dvh bg-canvas">
    <header class="sticky top-0 z-30 border-b border-line bg-surface/85 backdrop-blur">
        <div class="mx-auto flex h-16 w-full max-w-3xl items-center justify-between px-5">
            <div class="flex items-center gap-2.5">
                <x-tk.logo class="h-8" />
                <span class="rounded-pill bg-tk-100 px-2.5 py-0.5 text-xs font-semibold text-content">Livreur</span>
            </div>
            <div class="flex items-center gap-3 text-sm">
                <span class="hidden text-muted sm:inline">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-tile px-3 py-2 text-content hover:bg-tk-100"><x-lucide-log-out class="h-4 w-4" /> Sortir</button>
                </form>
            </div>
        </div>
    </header>

    <main class="mx-auto w-full max-w-3xl px-5 py-8 pb-24">
        {{ $slot }}
    </main>

    <x-tk.toast />
    @livewireScripts
</body>
</html>
