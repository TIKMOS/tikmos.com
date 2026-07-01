<?php

use App\Enums\OrderStatus;
use App\Enums\QuoteStatus;
use App\Enums\ServiceRequestStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quote;
use App\Models\ServiceRequest;
use App\Settings\ShopSettings;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component
{
    /** @return array<string, mixed> */
    public function with(): array
    {
        $threshold = app(ShopSettings::class)->low_stock_threshold;

        $acceptedRevenue = (int) Quote::where('status', QuoteStatus::Accepted->value)->sum('amount');
        $ordersRevenue = (int) Order::where('status', OrderStatus::Delivered->value)->sum('total');

        return [
            'requestsMonth' => ServiceRequest::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->count(),
            'newRequests' => ServiceRequest::where('status', ServiceRequestStatus::Sent->value)->count(),
            'quotesSent' => Quote::where('status', QuoteStatus::Sent->value)->count(),
            'ordersMonth' => Order::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->count(),
            'revenue' => $acceptedRevenue + $ordersRevenue,
            'lowStock' => Product::query()->where('is_active', true)->whereNotNull('stock')->where('stock', '<=', $threshold)->count(),
            'recentRequests' => ServiceRequest::with('service')->latest()->take(6)->get(),
        ];
    }
}; ?>

<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <x-tk.kicker>Back-office</x-tk.kicker>
            <h1 class="mt-2 font-display text-3xl font-extrabold text-content">Tableau de bord</h1>
        </div>
        @can('services.manage')
            <x-tk.button href="{{ route('admin.services') }}" variant="primary" icon="plus">Gérer les services</x-tk.button>
        @endcan
    </div>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-tk.stat label="Demandes ce mois" :value="$requestsMonth" icon="file-text" tint="sky" />
        <x-tk.stat label="Devis envoyés" :value="$quotesSent" icon="receipt" tint="mint" />
        <x-tk.stat label="Commandes ce mois" :value="$ordersMonth" icon="shopping-bag" tint="lilac" />
        <x-tk.stat label="CA estimé" :value="number_format($revenue, 0, ',', ' ').' F'" icon="trending-up" tint="peach" />
    </div>

    {{-- Alertes --}}
    <div class="mt-4 space-y-3">
        @if ($newRequests > 0)
            <a href="{{ route('admin.service-requests') }}" wire:navigate class="group flex items-center gap-3 rounded-card border border-info/20 bg-info-soft px-5 py-4 text-sm text-info-ink transition-colors hover:opacity-90">
                <x-lucide-inbox class="h-5 w-5" />
                <span><span class="font-medium">{{ $newRequests }}</span> nouvelle(s) demande(s)</span> à qualifier.
                <x-lucide-arrow-right class="ml-auto h-4 w-4 transition-transform group-hover:translate-x-0.5" />
            </a>
        @endif
        @if ($lowStock > 0)
            @can('shop.products.manage')
                <a href="{{ route('admin.boutique') }}" wire:navigate class="group flex items-center gap-3 rounded-card border border-warning/20 bg-warning-soft px-5 py-4 text-sm text-warning-ink transition-colors hover:opacity-90">
                    <x-lucide-triangle-alert class="h-5 w-5" />
                    <span><span class="font-medium">{{ $lowStock }}</span> produit(s)</span> en stock bas.
                    <x-lucide-arrow-right class="ml-auto h-4 w-4 transition-transform group-hover:translate-x-0.5" />
                </a>
            @endcan
        @endif
    </div>

    {{-- Demandes récentes --}}
    <div class="mt-8 flex items-center justify-between">
        <x-tk.kicker>Dernières demandes</x-tk.kicker>
        @can('demande.view')<a href="{{ route('admin.service-requests') }}" wire:navigate class="text-sm font-medium text-content hover:underline">Tout voir</a>@endcan
    </div>
    <div class="mt-4">
        @forelse ($recentRequests as $request)
            <a href="{{ route('admin.service-requests.show', $request->reference) }}" wire:navigate class="flex items-center justify-between gap-4 border-b border-line py-3.5 hover:bg-tk-50">
                <div>
                    <p class="font-mono text-sm font-bold text-content">{{ $request->reference }}</p>
                    <p class="text-xs text-muted">{{ $request->name }} · {{ $request->service?->title ?? 'Demande sur mesure' }}</p>
                </div>
                <x-tk.badge :tone="$request->status->tone()" dot>{{ $request->status->label() }}</x-tk.badge>
            </a>
        @empty
            <x-tk.empty-state icon="inbox" title="Aucune demande">Les demandes de devis apparaîtront ici.</x-tk.empty-state>
        @endforelse
    </div>
</div>
