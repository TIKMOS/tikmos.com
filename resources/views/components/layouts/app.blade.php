@props(['title' => null])

@php
    $nav = [
        ['label' => 'Accueil', 'icon' => 'home', 'route' => 'app.dashboard'],
        ['label' => 'Mes devis', 'icon' => 'file-text', 'route' => 'app.requests'],
        ['label' => 'Devis', 'icon' => 'plus', 'route' => 'services', 'fab' => true],
        ['label' => 'Achats', 'icon' => 'shopping-bag', 'route' => 'app.orders'],
        ['label' => 'Compte', 'icon' => 'user', 'route' => 'app.compte'],
    ];
    $bottomItems = array_map(fn ($i) => [...$i, 'active' => request()->routeIs($i['route'])], $nav);
@endphp

<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#011859">
    @include('partials.pwa')
    <title>{{ $title ? $title.' — Tikmos' : 'Tikmos' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-dvh bg-canvas pb-24 md:pb-0">
    <header class="sticky top-0 z-30 border-b border-line bg-surface/85 backdrop-blur">
        <div class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-5 sm:px-6 lg:px-8">
            <div class="flex items-center gap-8">
                <a href="{{ route('app.dashboard') }}" wire:navigate aria-label="Accueil"><x-tk.logo /></a>
                <nav class="hidden items-center gap-6 text-sm font-medium md:flex">
                    @foreach (['app.dashboard' => 'Accueil', 'app.requests' => 'Mes devis', 'app.orders' => 'Mes achats'] as $route => $label)
                        <a href="{{ route($route) }}" wire:navigate
                           class="{{ request()->routeIs($route) ? 'text-content' : 'text-content hover:text-content' }}">{{ $label }}</a>
                    @endforeach
                </nav>
            </div>

            <div class="relative flex items-center gap-3" x-data="{ open: false }">
                <button class="flex items-center gap-2 rounded-pill py-1 pl-1 pr-3 hover:bg-tk-100" @click="open = !open">
                    <x-tk.avatar :name="auth()->user()->name" size="sm" />
                    <span class="hidden text-sm font-medium text-content sm:inline">{{ auth()->user()->name }}</span>
                    <x-lucide-chevron-down class="h-4 w-4 text-tk-400" />
                </button>
                <div x-show="open" x-cloak @click.outside="open = false" x-transition.origin.top.right
                     class="absolute right-0 top-12 w-48 rounded-card border border-line bg-surface p-1.5 shadow-pop">
                    <a href="{{ route('app.orders') }}" wire:navigate class="flex items-center gap-2 rounded-tile px-3 py-2 text-sm hover:bg-tk-100"><x-lucide-shopping-bag class="h-4 w-4" /> Mes achats</a>
                    <a href="{{ route('app.compte') }}" wire:navigate class="flex items-center gap-2 rounded-tile px-3 py-2 text-sm hover:bg-tk-100"><x-lucide-user class="h-4 w-4" /> Mon compte</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2 rounded-tile px-3 py-2 text-sm text-danger hover:bg-danger-soft"><x-lucide-log-out class="h-4 w-4" /> Déconnexion</button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <main class="mx-auto w-full max-w-6xl px-5 py-8 sm:px-6 lg:px-8">
        {{ $slot }}
    </main>

    <x-tk.nav-bottom :items="$bottomItems" />

    <x-tk.toast />
    @livewireScripts
</body>
</html>
