{{-- Toaster Alpine, sans dépendance. Écoute window 'sz-toast' + flash session('toast'). --}}
<div
    x-data="{
        toasts: [],
        push(detail) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, type: detail.type || 'info', message: detail.message || '' });
            setTimeout(() => this.remove(id), 5000);
        },
        remove(id) { this.toasts = this.toasts.filter(t => t.id !== id); },
    }"
    x-on:sz-toast.window="push($event.detail)"
    @if (session('toast'))
        x-init="push(@js(session('toast')))"
    @endif
    class="pointer-events-none fixed inset-x-0 bottom-24 z-[60] flex flex-col items-center gap-2 px-4 md:bottom-6 md:right-6 md:left-auto md:items-end"
    aria-live="polite"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-end="opacity-0"
            class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-card border border-line bg-surface px-4 py-3 shadow-pop"
            :class="{
                'border-l-4 border-l-[var(--color-success)]': toast.type === 'success',
                'border-l-4 border-l-[var(--color-danger)]': toast.type === 'error',
                'border-l-4 border-l-[var(--color-warning)]': toast.type === 'warning',
                'border-l-4 border-l-[var(--color-info)]': toast.type === 'info',
            }"
        >
            <p class="flex-1 text-sm text-content" x-text="toast.message"></p>
            <button type="button" class="text-tk-400 hover:text-content" @click="remove(toast.id)" aria-label="Fermer">
                <x-lucide-x class="h-4 w-4" />
            </button>
        </div>
    </template>
</div>
