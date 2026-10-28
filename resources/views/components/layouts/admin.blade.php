@props(['title' => null])

@php
    $links = [
        ['route' => 'admin.dashboard', 'label' => 'Tableau de bord', 'icon' => 'layout-dashboard', 'perm' => null],
        ['route' => 'admin.service-requests', 'label' => 'Devis & demandes', 'icon' => 'receipt', 'perm' => 'demande.view'],
        ['route' => 'admin.services', 'label' => 'Services', 'icon' => 'layers', 'perm' => 'services.manage'],
        ['route' => 'admin.projects', 'label' => 'Réalisations', 'icon' => 'folder-open', 'perm' => 'projects.manage'],
        ['route' => 'admin.blog', 'label' => 'Blog', 'icon' => 'newspaper', 'perm' => 'blog.manage'],
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
    <meta name="theme-color" content="#0b0b0c">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.pwa')
    <title>{{ $title ? $title.' — Tikmos Admin' : 'Tikmos Admin' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-dvh bg-canvas" x-data="{ open: false, desktop: window.matchMedia('(min-width: 1024px)').matches }"
      @resize.window.debounce.100ms="const next = window.matchMedia('(min-width: 1024px)').matches; if (next !== desktop) { const inside = $refs.sidebar.contains(document.activeElement); desktop = next; open = false; if (!desktop && inside) $nextTick(() => $refs.sidebarTrigger.focus()) }">
    <x-tk.skip-link />
    {{-- Sidebar --}}
    <aside id="admin-navigation" x-ref="sidebar" :inert="!desktop && !open" :aria-hidden="!desktop && !open ? 'true' : null"
           :role="!desktop ? 'dialog' : null" :aria-modal="!desktop && open ? 'true' : null" aria-label="Navigation administration"
           x-trap.inert.noscroll.noreturn="open && !desktop" @keydown.escape.stop.prevent="if (open) { open = false; $nextTick(() => $refs.sidebarTrigger.focus()) }"
           class="invisible fixed inset-y-0 left-0 z-40 w-64 -translate-x-full overflow-y-auto border-r border-line bg-ink text-paper transition-transform lg:visible lg:translate-x-0"
           :class="open && '!translate-x-0 !visible'">
        <div class="flex h-16 items-center justify-between px-5">
            <a href="{{ route('admin.dashboard') }}" wire:navigate class="focus-visible:outline-paper"><x-tk.logo invert /></a>
            <button type="button" class="grid h-11 w-11 place-items-center text-tk-400 focus-visible:outline-paper lg:hidden" @click="open = false; $nextTick(() => $refs.sidebarTrigger.focus())" aria-label="Fermer la navigation"><x-lucide-x class="h-5 w-5" /></button>
        </div>
        <nav aria-label="Administration" class="flex flex-col gap-0.5 px-3 py-4">
            @foreach ($links as $link)
                @php($active = request()->routeIs($link['route'].'*'))
                <a href="{{ route($link['route']) }}" wire:navigate
                   class="flex min-h-11 items-center gap-3 rounded-tile px-3 py-2.5 text-sm font-medium transition-colors focus-visible:outline-paper {{ $active ? 'bg-white/10 text-white' : 'text-tk-400 hover:bg-white/5 hover:text-white' }}">
                    <x-dynamic-component :component="'lucide-'.$link['icon']" class="h-[18px] w-[18px]" />
                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>
    </aside>

    {{-- Overlay mobile --}}
    <div x-show="open && !desktop" x-cloak @click="open = false; $nextTick(() => $refs.sidebarTrigger.focus())" class="fixed inset-0 z-30 bg-black/40 lg:hidden"></div>

    {{-- Contenu --}}
    <div class="lg:pl-64">
        <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-line bg-surface/85 px-5 backdrop-blur sm:px-6">
            <button type="button" x-ref="sidebarTrigger" :aria-expanded="open" aria-controls="admin-navigation" aria-haspopup="dialog" class="grid h-11 w-11 place-items-center text-content lg:hidden" @click="open = true" aria-label="Navigation administration"><x-lucide-menu class="h-6 w-6" /></button>
            <div class="ml-auto flex items-center gap-3" x-data="{ menu: false }" @keydown.escape.stop.prevent="if (menu) { menu = false; $refs.accountTrigger.focus() }" @focusout="if (!$el.contains($event.relatedTarget)) menu = false">
                <a href="{{ url('/') }}" class="hidden text-sm text-muted hover:text-content sm:inline">Voir le site</a>
                <button type="button" x-ref="accountTrigger" aria-label="Mon compte" :aria-expanded="menu" aria-controls="admin-account-menu" class="flex min-h-11 min-w-11 items-center gap-2 rounded-pill py-1 pl-1 pr-3 hover:bg-tk-100" @click="menu = !menu; if (menu) $nextTick(() => $refs.accountPanel.querySelector('a')?.focus())">
                    <x-tk.avatar :name="auth()->user()->name" size="sm" />
                    <span class="hidden text-sm font-medium text-content sm:inline">{{ auth()->user()->name }}</span>
                </button>
                <div id="admin-account-menu" x-ref="accountPanel" x-show="menu" x-cloak @click.outside="menu = false" class="absolute right-5 top-14 w-44 rounded-card border border-line bg-surface p-1.5 shadow-pop">
                    <a href="{{ route('app.dashboard') }}" wire:navigate class="block rounded-tile px-3 py-2 text-sm hover:bg-tk-100">Espace client</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf
                        <button type="submit" class="flex w-full items-center gap-2 rounded-tile px-3 py-2 text-sm text-danger hover:bg-danger-soft"><x-lucide-log-out class="h-4 w-4" /> Déconnexion</button>
                    </form>
                </div>
            </div>
        </header>

        <main id="main-content" tabindex="-1" class="mx-auto w-full max-w-6xl px-5 py-8 sm:px-6">
            {{ $slot }}
        </main>
    </div>

    <x-tk.toast />
    @livewireScripts
</body>
</html>
