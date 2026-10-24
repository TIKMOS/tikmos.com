<x-layouts.auth title="Recevoir un lien d’accès">
    <div class="min-w-0">
        <h1 class="font-display text-3xl font-extrabold text-content">Recevoir un lien d’accès</h1>
        <p class="mt-3 text-sm text-muted">Pour une demande déposée sans compte, indiquez sa référence et l’adresse email utilisée lors de l’envoi. Le lien sera envoyé uniquement à cette adresse.</p>
        @if (session('status'))
            <p role="status" class="mt-5 rounded-tile bg-success-soft p-4 text-sm text-success-ink">{{ session('status') }}</p>
        @endif
        <form method="POST" action="{{ route('request-recovery.renew.send') }}" class="mt-6 space-y-5">
            @csrf
            <x-tk.field label="Référence de la demande" for="recovery-reference" :error="$errors->first('reference')">
                <x-tk.input id="recovery-reference" name="reference" :value="old('reference')" required maxlength="80" placeholder="TKM-DEV-…" :invalid="$errors->has('reference')" />
            </x-tk.field>
            <x-tk.field label="Email de la demande" for="recovery-email" :error="$errors->first('email')">
                <x-tk.input id="recovery-email" name="email" type="email" :value="old('email')" required maxlength="255" autocomplete="email" :invalid="$errors->has('email')" />
            </x-tk.field>
            <x-tk.button type="submit" class="w-full whitespace-normal">Recevoir un nouveau lien</x-tk.button>
        </form>
        <p class="mt-6 text-sm text-muted">Déjà rattachée à votre compte ? <a href="{{ route('app.requests') }}" class="font-medium text-content underline">Mes demandes</a></p>
        <p class="mt-4 text-sm text-muted">Sans adresse email ou sans référence ? <a href="{{ route('contact') }}" class="font-medium text-content underline">Contactez l’équipe</a> pour une vérification de votre identité.</p>
    </div>
</x-layouts.auth>
