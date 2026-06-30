<?php

use App\Models\LoginLog;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.admin')] class extends Component
{
    use WithPagination;

    /** @return array<string, mixed> */
    public function with(): array
    {
        return ['logs' => LoginLog::with('user')->latest('logged_in_at')->paginate(25)];
    }
}; ?>

<div>
    <x-tk.kicker tone="accent">Back-office</x-tk.kicker>
    <h1 class="mt-2 font-display text-3xl font-extrabold text-content">Audit — connexions</h1>

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
                @forelse ($logs as $log)
                    <tr wire:key="log-{{ $log->id }}" class="border-b border-line">
                        <td class="py-3 pr-4 text-content">{{ $log->user?->name ?? $log->email ?? '—' }}</td>
                        <td class="py-3 pr-4">
                            <x-tk.badge :tone="$log->action === 'failed' ? 'danger' : ($log->action === 'login' ? 'success' : 'neutral')">{{ $log->action }}</x-tk.badge>
                        </td>
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

    @if ($logs->hasPages())<div class="mt-6">{{ $logs->links() }}</div>@endif
</div>
