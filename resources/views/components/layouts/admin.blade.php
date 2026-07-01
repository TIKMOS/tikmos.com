@props(['title' => null])

@php
    $links = [
        ['route' => 'admin.dashboard', 'label' => 'Tableau de bord', 'icon' => 'layout-dashboard', 'perm' => null],
        ['route' => 'admin.service-requests', 'label' => 'Devis & demandes', 'icon' => 'receipt', 'perm' => 'demande.view'],
        ['route' => 'admin.services', 'label' => 'Services', 'icon' => 'layers', 'perm' => 'services.manage'],
        ['route' => 'admin.projects', 'label' => 'Réalisations', 'icon' => 'folder-open', 'perm' => 'projects.manage'],
        ['route' => 'admin.boutique', 'label' => 'Boutique', 'icon' => 'shopping-bag', 'perm' => 'shop.products.manage'],
        ['route' => 'admin.boutique.orders', 'label' => 'Commandes', 'icon' => 'package', 'perm' => 'shop.orders.view'],
        ['route' => 'admin.clients', 'label' => 'Clients', 'icon' => 'users', 'perm' => 'client.view'],
        ['route' => 'admin.messages', 'label' => 'Messages', 'icon' => 'mail', 'perm' => 'message.view'],
        ['route' => 'admin.equipe', 'label' => 'Équipe', 'icon' => 'user-cog', 'perm' => 'equipe.view'],
        ['route' => 'admin.audit', 'label' => 'Audit', 'icon' => 'history', 'perm' => 'audit.view'],
        ['route' => 'admin.reglages', 'label' => 'Réglages', 'icon' => 'settings', 'perm' => 'settings.manage'],
    ];
    $links = array_filter($links, fn ($l) => $l['perm'] === null || auth()->user()->can($l['perm']));
@endphp

<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#17306e">
    @include('partials.pwa')
    <title>{{ $title ? $title.' — Tikmos Admin' : 'Tikmos Admin' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-dvh bg-canvas" x-data="{ open: false }">
    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full border-r border-line bg-ink text-paper transition-transform lg:translate-x-0"
           :class="open && '!translate-x-0'">
        <div class="flex h-16 items-center justify-between px-5">
            <a href="{{ route('admin.dashboard') }}" wire:navigate><x-tk.logo invert /></a>
            <button class="text-tk-400 lg:hidden" @click="open = false" aria-label="Fermer"><x-lucide-x class="h-5 w-5" /></button>
        </div>
        <nav class="flex flex-col gap-0.5 px-3 py-4">
            @foreach ($links as $link)
                @php($active = request()->routeIs($link['route'].'*'))
                <a href="{{ route($link['route']) }}" wire:navigate
                   class="flex items-center gap-3 rounded-tile px-3 py-2.5 text-sm font-medium transition-colors {{ $active ? 'bg-white/10 text-white' : 'text-tk-400 hover:bg-white/5 hover:text-white' }}">
                    <x-dynamic-component :component="'lucide-'.$link['icon']" class="h-[18px] w-[18px]" />
                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>
    </aside>

    {{-- Overlay mobile --}}
    <div x-show="open" x-cloak @click="open = false" class="fixed inset-0 z-30 bg-black/40 lg:hidden"></div>

    {{-- Contenu --}}
    <div class="lg:pl-64">
        <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-line bg-surface/85 px-5 backdrop-blur sm:px-6">
            <button class="text-content lg:hidden" @click="open = true" aria-label="Menu"><x-lucide-menu class="h-6 w-6" /></button>
            <div class="ml-auto flex items-center gap-3" x-data="{ menu: false }">
                <a href="{{ url('/') }}" class="hidden text-sm text-muted hover:text-content sm:inline">Voir le site</a>
                <button class="flex items-center gap-2 rounded-pill py-1 pl-1 pr-3 hover:bg-tk-100" @click="menu = !menu">
                    <x-tk.avatar :name="auth()->user()->name" size="sm" />
                    <span class="hidden text-sm font-medium text-content sm:inline">{{ auth()->user()->name }}</span>
                </button>
                <div x-show="menu" x-cloak @click.outside="menu = false" class="absolute right-5 top-14 w-44 rounded-card border border-line bg-surface p-1.5 shadow-pop">
                    <a href="{{ route('app.dashboard') }}" wire:navigate class="block rounded-tile px-3 py-2 text-sm hover:bg-tk-100">Espace client</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf
                        <button type="submit" class="flex w-full items-center gap-2 rounded-tile px-3 py-2 text-sm text-danger hover:bg-danger-soft"><x-lucide-log-out class="h-4 w-4" /> Déconnexion</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl px-5 py-8 sm:px-6">
            {{ $slot }}
        </main>
    </div>

    <x-tk.toast />
    @livewireScripts
</body>
</html>
