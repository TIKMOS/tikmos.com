<?php

use App\Enums\QuoteStatus;
use App\Enums\ServiceRequestStatus;
use App\Models\Quote;
use App\Models\ServiceRequest;
use App\Notifications\QuoteSent;
use App\Notifications\ServiceRequestStatusChanged;
use App\Support\ReferenceGenerator;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component
{
    public ServiceRequest $request;

    public string $note = '';

    // Devis
    public int $amount = 0;

    public string $currency = 'XOF';

    public string $validUntil = '';

    public string $quoteBody = '';

    public function mount(ServiceRequest $request): void
    {
        $this->request = $request->load(['service', 'user', 'assignee', 'quote', 'events.author']);

        if ($q = $this->request->quote) {
            $this->amount = $q->amount;
            $this->currency = $q->currency;
            $this->validUntil = $q->valid_until?->format('Y-m-d') ?? '';
            $this->quoteBody = (string) $q->body;
        }
    }

    public function transition(string $status): void
    {
        abort_unless(Auth::user()?->can('demande.manage'), 403);

        $target = ServiceRequestStatus::from($status);
        $this->request->transitionTo($target, Auth::user(), $this->note ?: null);
        $this->notifyClient(new ServiceRequestStatusChanged($this->request));
        $this->reset('note');
        $this->request->refresh()->load('events.author');
        $this->dispatch('tk-toast', type: 'success', message: 'Statut mis à jour : '.$target->label());
    }

    /** Notifie le client (compte lié, sinon email de la demande). */
    private function notifyClient(BaseNotification $notification): void
    {
        if ($this->request->user) {
            $this->request->user->notify($notification);
        } elseif ($this->request->email) {
            Notification::route('mail', $this->request->email)->notify($notification);
        }
    }

    public function assignToMe(): void
    {
        abort_unless(Auth::user()?->can('demande.manage'), 403);

        $this->request->update(['assigned_to' => Auth::id()]);
        $this->request->refresh()->load('assignee');
        $this->dispatch('tk-toast', type: 'success', message: 'Demande affectée.');
    }

    public function saveQuote(): void
    {
        abort_unless(Auth::user()?->can('quotes.send'), 403);

        $data = $this->validate([
            'amount' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'max:8'],
            'validUntil' => ['nullable', 'date'],
            'quoteBody' => ['nullable', 'string', 'max:5000'],
        ]);

        $quote = $this->request->quote ?: new Quote([
            'service_request_id' => $this->request->id,
            'reference' => ReferenceGenerator::quote(),
        ]);

        $quote->fill([
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'valid_until' => $data['validUntil'] ?: null,
            'body' => $data['quoteBody'] ?: null,
            'status' => QuoteStatus::Sent,
            'sent_at' => now(),
        ]);
        $quote->save();

        // Le statut de la demande passe à « Devis envoyé ».
        if ($this->request->status !== ServiceRequestStatus::Quoted) {
            $this->request->transitionTo(ServiceRequestStatus::Quoted, Auth::user(), 'Devis '.$quote->reference.' envoyé.');
        }

        $this->request->refresh()->load(['quote', 'events.author']);
        $this->notifyClient(new QuoteSent($this->request));
        $this->dispatch('tk-toast', type: 'success', message: 'Devis '.$quote->reference.' enregistré et envoyé.');
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        return ['pipeline' => ServiceRequestStatus::cases()];
    }
}; ?>

<div>
    <a href="{{ route('admin.service-requests') }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-muted hover:text-content"><x-lucide-arrow-left class="h-4 w-4" /> Demandes</a>

    <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="font-display text-3xl font-extrabold text-content">{{ $request->name }}</h1>
            <p class="mt-1 font-mono text-sm text-muted">{{ $request->reference }} · <x-tk.badge :tone="$request->status->tone()">{{ $request->status->label() }}</x-tk.badge></p>
        </div>
        @can('demande.manage')
            <x-tk.button wire:click="assignToMe" variant="secondary" icon="user-check">{{ $request->assignee ? 'Affectée à '.$request->assignee->name : "M'affecter" }}</x-tk.button>
        @endcan
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        {{-- Colonne principale --}}
        <div class="space-y-6 lg:col-span-2">
            <x-tk.admin-section title="Demande" icon="file-text">
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div><dt class="text-xs uppercase text-muted">Service</dt><dd class="mt-0.5 text-content">{{ $request->service?->title ?? '—' }}</dd></div>
                    <div><dt class="text-xs uppercase text-muted">Email</dt><dd class="mt-0.5 text-content">{{ $request->email ?: '—' }}</dd></div>
                    <div><dt class="text-xs uppercase text-muted">Téléphone</dt><dd class="mt-0.5 text-content">{{ $request->phone ?: '—' }}</dd></div>
                    <div><dt class="text-xs uppercase text-muted">Entreprise</dt><dd class="mt-0.5 text-content">{{ $request->company ?: '—' }}</dd></div>
                    <div><dt class="text-xs uppercase text-muted">Budget indicatif</dt><dd class="mt-0.5 text-content">{{ $request->budget_hint ?: '—' }}</dd></div>
                </dl>
                <div class="mt-4 rounded-tile bg-canvas p-4 text-sm text-content/80">{{ $request->message }}</div>
            </x-tk.admin-section>

            {{-- Devis --}}
            @can('quotes.send')
                <x-tk.admin-section title="Devis" icon="receipt">
                    <form wire:submit="saveQuote" class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-3">
                            <x-tk.field label="Montant" :error="$errors->first('amount')"><x-tk.input wire:model="amount" type="number" min="0" /></x-tk.field>
                            <x-tk.field label="Devise"><x-tk.input wire:model="currency" /></x-tk.field>
                            <x-tk.field label="Valide jusqu'au"><x-tk.input wire:model="validUntil" type="date" /></x-tk.field>
                        </div>
                        <x-tk.field label="Détail / conditions">
                            <textarea wire:model="quoteBody" rows="3" class="w-full rounded-input border border-tk-300 bg-surface px-4 py-3 text-[15px] text-content focus:border-ink focus:outline-none focus:ring-4 focus:ring-ink/10"></textarea>
                        </x-tk.field>
                        <x-tk.button type="submit" variant="primary" icon="send">{{ $request->quote ? 'Mettre à jour & renvoyer' : 'Créer & envoyer le devis' }}</x-tk.button>
                        @if ($request->quote)
                            <p class="text-sm text-muted">Devis actuel : <span class="font-mono font-bold text-content">{{ $request->quote->reference }}</span> — {{ $request->quote->amountLabel() }} · {{ $request->quote->status->label() }}</p>
                        @endif
                    </form>
                </x-tk.admin-section>
            @endcan

            {{-- Timeline --}}
            <x-tk.admin-section title="Historique" icon="history">
                <ol class="space-y-3">
                    @forelse ($request->events as $event)
                        <li wire:key="ev-{{ $event->id }}" class="flex gap-3">
                            <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-gold"></span>
                            <div>
                                <p class="text-sm text-content"><strong>{{ $event->to_status->label() }}</strong>@if ($event->from_status) <span class="text-muted">(depuis {{ $event->from_status->label() }})</span>@endif</p>
                                @if ($event->note)<p class="text-sm text-muted">{{ $event->note }}</p>@endif
                                <p class="text-xs text-tk-400">{{ $event->created_at->diffForHumans() }}@if ($event->author) · {{ $event->author->name }}@endif</p>
                            </div>
                        </li>
                    @empty
                        <li class="text-sm text-muted">Aucun événement.</li>
                    @endforelse
                </ol>
            </x-tk.admin-section>
        </div>

        {{-- Colonne actions --}}
        <div class="space-y-6">
            @can('demande.manage')
                <x-tk.admin-section title="Changer le statut" icon="git-branch">
                    <x-tk.field label="Note (optionnelle)"><x-tk.input wire:model="note" placeholder="Commentaire interne" /></x-tk.field>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($pipeline as $st)
                            @if ($st !== $request->status)
                                <button wire:click="transition('{{ $st->value }}')" class="rounded-btn border border-tk-300 px-3 py-1.5 text-sm font-medium text-content transition-colors hover:border-ink">{{ $st->label() }}</button>
                            @endif
                        @endforeach
                    </div>
                </x-tk.admin-section>
            @endcan
        </div>
    </div>
</div>
