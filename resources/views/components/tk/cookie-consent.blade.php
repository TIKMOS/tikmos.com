{{-- Bandeau de consentement cookies (RGPD). Accept/Refus à égalité, préférences granulaires. --}}
<div x-data="szCookieConsent()" x-show="visible" x-cloak x-transition.opacity
     class="fixed inset-x-0 bottom-0 z-[60] p-3 sm:p-4 pb-safe" role="dialog" aria-modal="false" aria-label="Consentement cookies">
    <div class="mx-auto w-full max-w-3xl rounded-card border border-line bg-surface p-5 shadow-pop sm:p-6">
        <div class="flex items-start gap-3">
            <span class="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-tile bg-tk-100 text-content"><x-lucide-cookie class="h-5 w-5" /></span>
            <div class="min-w-0">
                <p class="font-display font-bold text-content">On respecte votre vie privée</p>
                <p class="mt-1 text-sm text-muted">Nous utilisons des cookies nécessaires au fonctionnement du site. Avec votre accord, nous pouvons aussi mesurer l'audience pour l'améliorer. <a href="{{ url('/cookies') }}" class="font-medium text-content underline underline-offset-2">En savoir plus</a>.</p>
            </div>
        </div>

        {{-- Préférences granulaires --}}
        <div x-show="showPrefs" x-collapse class="mt-4 space-y-3 border-t border-line pt-4">
            <div class="flex items-center justify-between gap-4">
                <div><p class="text-sm font-medium text-content">Nécessaires</p><p class="text-xs text-muted">Indispensables (session, sécurité). Toujours actifs.</p></div>
                <span class="rounded-pill bg-tk-100 px-3 py-1 text-xs font-semibold text-tk-600">Toujours</span>
            </div>
            <label class="flex items-center justify-between gap-4 cursor-pointer">
                <span><span class="block text-sm font-medium text-content">Mesure d'audience</span><span class="block text-xs text-muted">Statistiques anonymes de visite.</span></span>
                <input type="checkbox" x-model="prefs.analytics" class="h-5 w-5 rounded border-tk-300 text-ink focus:ring-ink/20">
            </label>
            <label class="flex items-center justify-between gap-4 cursor-pointer">
                <span><span class="block text-sm font-medium text-content">Marketing</span><span class="block text-xs text-muted">Personnalisation et campagnes.</span></span>
                <input type="checkbox" x-model="prefs.marketing" class="h-5 w-5 rounded border-tk-300 text-ink focus:ring-ink/20">
            </label>
        </div>

        <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:justify-end">
            <template x-if="!showPrefs">
                <button @click="showPrefs = true" class="rounded-btn border border-tk-300 px-4 py-2.5 text-sm font-medium text-content transition-colors hover:border-ink">Personnaliser</button>
            </template>
            <template x-if="showPrefs">
                <button @click="saveChoices()" class="rounded-btn border border-tk-300 px-4 py-2.5 text-sm font-medium text-content transition-colors hover:border-ink">Enregistrer mes choix</button>
            </template>
            <button @click="rejectAll()" class="rounded-btn border border-tk-300 px-4 py-2.5 text-sm font-medium text-content transition-colors hover:border-ink">Tout refuser</button>
            <button @click="acceptAll()" class="rounded-btn bg-ink px-4 py-2.5 text-sm font-medium text-paper transition-colors hover:bg-tk-800">Tout accepter</button>
        </div>
    </div>
</div>
