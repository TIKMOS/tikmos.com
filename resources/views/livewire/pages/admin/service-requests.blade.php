<?php

use App\Enums\ServiceRequestStatus;
use App\Models\ServiceRequest;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.admin')] class extends Component
{
    use WithPagination;

    #[Url(as: 'statut')]
    public string $status = '';

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->resetPage();
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        $query = ServiceRequest::query()->with(['service', 'assignee'])->latest();

        if ($this->status !== '' && ServiceRequestStatus::tryFrom($this->status)) {
            $query->where('status', $this->status);
        }

        return [
            'requests' => $query->paginate(15),
            'statuses' => ServiceRequestStatus::cases(),
            'counts' => ServiceRequest::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')->pluck('total', 'status'),
        ];
    }
}; ?>

<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-display text-3xl font-extrabold text-content">Devis & demandes</h1>
            <p class="mt-1 text-muted">Pipeline des demandes de services reçues.</p>
        </div>
    </div>

    {{-- Filtres statut --}}
    <div class="mt-6 flex flex-wrap gap-2">
        <button wire:click="setStatus('')" class="rounded-btn border px-3 py-1.5 text-sm font-medium transition-colors {{ $status === '' ? 'border-ink bg-ink text-paper' : 'border-tk-300 text-content hover:border-ink' }}">Toutes</button>
        @foreach ($statuses as $st)
            <button wire:click="setStatus('{{ $st->value }}')" class="rounded-btn border px-3 py-1.5 text-sm font-medium transition-colors {{ $status === $st->value ? 'border-ink bg-ink text-paper' : 'border-tk-300 text-content hover:border-ink' }}">
                {{ $st->label() }} <span class="text-tk-400">{{ $counts[$st->value] ?? 0 }}</span>
            </button>
        @endforeach
    </div>

    <div class="mt-6">
        @if ($requests->isEmpty())
            <x-tk.empty-state icon="inbox" title="Aucune demande">Les demandes de devis apparaîtront ici.</x-tk.empty-state>
        @else
            <div class="overflow-hidden rounded-card border border-line bg-surface">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-line bg-canvas text-xs uppercase tracking-wide text-muted">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Référence</th>
                            <th class="px-4 py-3 font-semibold">Demandeur</th>
                            <th class="hidden px-4 py-3 font-semibold md:table-cell">Service</th>
                            <th class="px-4 py-3 font-semibold">Statut</th>
                            <th class="hidden px-4 py-3 font-semibold sm:table-cell">Reçue</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($requests as $request)
                            <tr wire:key="req-{{ $request->id }}" class="hover:bg-canvas/60">
                                <td class="px-4 py-3"><span class="font-mono text-xs font-bold text-content">{{ $request->reference }}</span></td>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-content">{{ $request->name }}</p>
                                    <p class="text-xs text-muted">{{ $request->email ?: $request->phone }}</p>
                                </td>
                                <td class="hidden px-4 py-3 text-muted md:table-cell">{{ $request->service?->title ?? '—' }}</td>
                                <td class="px-4 py-3"><x-tk.badge :tone="$request->status->tone()">{{ $request->status->label() }}</x-tk.badge></td>
                                <td class="hidden px-4 py-3 text-muted sm:table-cell">{{ $request->created_at->diffForHumans() }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.service-requests.show', $request->reference) }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-content hover:underline">Ouvrir <x-lucide-arrow-right class="h-4 w-4" /></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $requests->links() }}</div>
        @endif
    </div>
</div>
