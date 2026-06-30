<?php

use App\Enums\RequestStatus;
use App\Models\ParcelRequest;
use App\Services\ParcelService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.admin')] class extends Component
{
    use WithPagination;

    public function convert(int $id, ParcelService $service): void
    {
        abort_unless(auth()->user()->can('demande.manage'), 403);
        $request = ParcelRequest::findOrFail($id);

        if ($request->status === RequestStatus::Converted) {
            return;
        }

        $parcel = $service->convertRequest($request, auth()->user());
        $this->dispatch('sz-toast', type: 'success', message: 'Demande convertie en colis '.$parcel->reference.'.');
    }

    public function reject(int $id): void
    {
        abort_unless(auth()->user()->can('demande.manage'), 403);
        ParcelRequest::findOrFail($id)->update(['status' => RequestStatus::Rejected->value]);
        $this->dispatch('sz-toast', type: 'info', message: 'Demande refusée.');
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        return [
            'requests' => ParcelRequest::with(['user', 'convertedParcel'])->latest()->paginate(15),
            'canManage' => auth()->user()->can('demande.manage'),
        ];
    }
}; ?>

<div>
    <x-tk.kicker tone="accent">Back-office</x-tk.kicker>
    <h1 class="mt-2 font-display text-3xl font-extrabold text-content">Demandes</h1>

    <div class="mt-6">
        @forelse ($requests as $request)
            <div wire:key="r-{{ $request->id }}" class="flex flex-col gap-3 border-b border-line py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <p class="font-display font-bold text-content">{{ $request->type->label() }}</p>
                        <x-tk.badge :tone="$request->status->tone()">{{ $request->status->label() }}</x-tk.badge>
                    </div>
                    <p class="mt-0.5 text-sm text-muted">
                        {{ $request->origin->label() }} → {{ $request->destination->label() }}
                        · {{ $request->user?->name ?? 'Client' }}
                        @if ($request->recipient_name) · pour {{ $request->recipient_name }} @endif
                    </p>
                    @if ($request->convertedParcel)
                        <a href="{{ route('admin.colis.show', $request->convertedParcel->reference) }}" wire:navigate class="text-xs font-medium text-content hover:underline">→ {{ $request->convertedParcel->reference }}</a>
                    @endif
                </div>
                @if ($canManage && ! in_array($request->status, [\App\Enums\RequestStatus::Converted, \App\Enums\RequestStatus::Rejected], true))
                    <div class="flex items-center gap-2">
                        <x-tk.button wire:click="convert({{ $request->id }})" variant="accent" size="sm" icon="package-plus">Convertir en colis</x-tk.button>
                        <button wire:click="reject({{ $request->id }})" wire:confirm="Refuser cette demande ?" class="rounded-tile px-3 py-2 text-sm font-medium text-danger hover:bg-danger-soft">Refuser</button>
                    </div>
                @endif
            </div>
        @empty
            <x-tk.empty-state icon="inbox" title="Aucune demande">Les demandes des clients apparaîtront ici.</x-tk.empty-state>
        @endforelse
    </div>

    @if ($requests->hasPages())<div class="mt-6">{{ $requests->links() }}</div>@endif
</div>
