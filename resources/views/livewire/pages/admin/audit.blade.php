<?php

use App\Audit\ActivityFeed;
use App\Models\LoginLog;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.admin')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $tab = 'activity';

    #[Url]
    public string $causer = '';

    #[Url]
    public string $event = '';

    #[Url]
    public string $logName = '';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    public ?int $expanded = null;

    public function updated(): void
    {
        $this->resetPage();
    }

    public function toggle(int $id): void
    {
        $this->expanded = $this->expanded === $id ? null : $id;
    }

    public function resetFilters(): void
    {
        $this->reset(['causer', 'event', 'logName', 'from', 'to']);
        $this->resetPage();
    }

    /** @return array<string, mixed> */
    public function with(ActivityFeed $feed): array
    {
        $facets = $feed->facets();

        return [
            'activities' => $feed->query([
                'causer_id' => $this->causer ?: null,
                'event' => $this->event ?: null,
                'log_name' => $this->logName ?: null,
                'from' => $this->from ?: null,
                'to' => $this->to ?: null,
            ])->paginate(20),
            'logins' => LoginLog::with('user')->latest('logged_in_at')->paginate(25),
            'logNames' => $facets['log_names'],
            'causers' => User::whereIn('id', \Spatie\Activitylog\Models\Activity::query()->distinct()->pluck('causer_id')->filter())
                ->orderBy('name')->get(['id', 'name']),
        ];
    }
}; ?>

<div>
    <x-tk.kicker tone="accent">Back-office</x-tk.kicker>
    <h1 class="mt-2 font-display text-3xl font-extrabold text-content">Audit & activité</h1>
    <p class="mt-2 max-w-2xl text-muted">Chaque action sensible est tracée (auteur, objet, valeurs avant/après, contexte).</p>

    {{-- Onglets --}}
    <div class="mt-6 flex gap-2 border-b border-line">
        <button wire:click="$set('tab', 'activity')" @class(['-mb-px border-b-2 px-4 py-2 text-sm font-semibold', 'border-ink text-content' => $tab === 'activity', 'border-transparent text-muted hover:text-content' => $tab !== 'activity'])>Activité métier</button>
        <button wire:click="$set('tab', 'logins')" @class(['-mb-px border-b-2 px-4 py-2 text-sm font-semibold', 'border-ink text-content' => $tab === 'logins', 'border-transparent text-muted hover:text-content' => $tab !== 'logins'])>Connexions</button>
    </div>

    @if ($tab === 'activity')
        {{-- Filtres --}}
        <div class="mt-5 grid gap-3 rounded-card border border-line bg-surface p-4 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label class="mb-1 block text-xs font-medium text-muted">Auteur</label>
                <select wire:model.live="causer" class="w-full rounded-input border border-tk-300 bg-surface px-3 py-2 text-sm text-content focus:border-ink focus:outline-none">
                    <option value="">Tous</option>
                    @foreach ($causers as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-muted">Action</label>
                <select wire:model.live="event" class="w-full rounded-input border border-tk-300 bg-surface px-3 py-2 text-sm text-content focus:border-ink focus:outline-none">
                    <option value="">Toutes</option>
                    @foreach (['created' => 'Création', 'updated' => 'Modification', 'deleted' => 'Suppression', 'restored' => 'Restauration'] as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-muted">Domaine</label>
                <select wire:model.live="logName" class="w-full rounded-input border border-tk-300 bg-surface px-3 py-2 text-sm text-content focus:border-ink focus:outline-none">
                    <option value="">Tous</option>
                    @foreach ($logNames as $ln)<option value="{{ $ln }}">{{ ucfirst($ln) }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-muted">Du</label>
                <input type="date" wire:model.live="from" class="w-full rounded-input border border-tk-300 bg-surface px-3 py-2 text-sm text-content focus:border-ink focus:outline-none">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-muted">Au</label>
                <input type="date" wire:model.live="to" class="w-full rounded-input border border-tk-300 bg-surface px-3 py-2 text-sm text-content focus:border-ink focus:outline-none">
            </div>
        </div>
        <div class="mt-2 flex justify-end">
            <button wire:click="resetFilters" class="text-xs font-medium text-muted hover:text-content hover:underline">Réinitialiser les filtres</button>
        </div>

        {{-- Table --}}
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-line text-xs uppercase tracking-wide text-muted">
                    <tr>
                        <th class="py-3 pr-4 font-semibold">Date</th>
                        <th class="py-3 pr-4 font-semibold">Auteur</th>
                        <th class="py-3 pr-4 font-semibold">Action</th>
                        <th class="py-3 pr-4 font-semibold">Objet</th>
                        <th class="py-3 pr-4 font-semibold">Contexte</th>
                        <th class="py-3 font-semibold"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($activities as $a)
                        @php($ctx = $a->properties['context'] ?? [])
                        @php($label = $a->properties['subject_label'] ?? (class_basename((string) $a->subject_type).' #'.$a->subject_id))
                        <tr wire:key="act-{{ $a->id }}" class="border-b border-line align-top">
                            <td class="py-3 pr-4 whitespace-nowrap text-muted">{{ $a->created_at->format('d/m/Y H:i') }}</td>
                            <td class="py-3 pr-4 text-content">{{ $a->causer?->name ?? 'Système' }}</td>
                            <td class="py-3 pr-4">
                                <x-tk.badge :tone="match ($a->event) { 'created' => 'success', 'deleted' => 'danger', 'updated' => 'info', default => 'neutral' }">{{ $a->event ?? $a->description }}</x-tk.badge>
                                <span class="ml-1 text-xs text-muted">{{ $a->log_name }}</span>
                            </td>
                            <td class="py-3 pr-4 text-content">{{ $label }} <span class="text-xs text-muted">({{ class_basename((string) $a->subject_type) }})</span></td>
                            <td class="py-3 pr-4 font-mono text-xs text-muted">{{ $ctx['ip'] ?? '—' }} · {{ $ctx['channel'] ?? '—' }}</td>
                            <td class="py-3">
                                @if ($a->event === 'updated' && ($a->properties['attributes'] ?? false))
                                    <button wire:click="toggle({{ $a->id }})" class="text-xs font-medium text-content hover:underline">{{ $expanded === $a->id ? 'Masquer' : 'Diff' }}</button>
                                @endif
                            </td>
                        </tr>
                        @if ($expanded === $a->id)
                            <tr wire:key="diff-{{ $a->id }}" class="border-b border-line bg-canvas">
                                <td colspan="6" class="px-4 py-3">
                                    <div class="grid gap-1 text-xs">
                                        @foreach (($a->properties['attributes'] ?? []) as $key => $new)
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="font-semibold text-content">{{ $key }}</span>
                                                <span class="rounded bg-danger/10 px-1.5 py-0.5 font-mono text-danger line-through">{{ \Illuminate\Support\Str::limit((string) (($a->properties['old'][$key] ?? '∅')), 60) }}</span>
                                                <x-lucide-arrow-right class="h-3 w-3 text-muted" />
                                                <span class="rounded bg-success/10 px-1.5 py-0.5 font-mono text-success">{{ \Illuminate\Support\Str::limit((string) $new, 60) }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="6" class="py-8 text-center text-muted">Aucune activité pour ces critères.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($activities->hasPages())<div class="mt-6">{{ $activities->links() }}</div>@endif
    @else
        {{-- Connexions --}}
        <div class="mt-6 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-line text-xs uppercase tracking-wide text-muted">
                    <tr>
                        <th class="py-3 pr-4 font-semibold">Utilisateur</th>
                        <th class="py-3 pr-4 font-semibold">Action</th>
                        <th class="py-3 pr-4 font-semibold">Appareil</th>
                        <th class="py-3 pr-4 font-semibold">IP</th>
                        <th class="py-3 font-semibold">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logins as $log)
                        <tr wire:key="log-{{ $log->id }}" class="border-b border-line">
                            <td class="py-3 pr-4 text-content">{{ $log->user?->name ?? $log->email ?? '—' }}</td>
                            <td class="py-3 pr-4"><x-tk.badge :tone="$log->action === 'failed' ? 'danger' : ($log->action === 'login' ? 'success' : 'neutral')">{{ $log->action }}</x-tk.badge></td>
                            <td class="py-3 pr-4 text-muted">{{ $log->browser }} · {{ $log->os }} ({{ $log->device_type }})</td>
                            <td class="py-3 pr-4 font-mono text-xs text-muted">{{ $log->ip_address }}</td>
                            <td class="py-3 text-muted">{{ $log->logged_in_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-8 text-center text-muted">Aucune connexion enregistrée.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logins->hasPages())<div class="mt-6">{{ $logins->links() }}</div>@endif
    @endif
</div>
