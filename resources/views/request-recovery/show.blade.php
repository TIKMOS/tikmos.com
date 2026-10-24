<x-layouts.auth title="Retrouver ma demande">
    <div class="min-w-0">
        <h1 class="font-display text-3xl font-extrabold text-content">Retrouver ma demande</h1>
        @if (session('status'))
            <p role="status" class="mt-5 rounded-tile bg-success-soft p-4 text-sm text-success-ink">{{ session('status') }}</p>
        @endif
        @error('recovery')<p role="alert" class="mt-5 rounded-tile bg-danger-soft p-4 text-sm text-danger-ink">{{ $message }}</p>@enderror

        <div class="mt-5 space-y-5 text-sm text-content">
            @if ($state === 'guest')
                <p>Connectez-vous ou créez un compte avec l’adresse email qui a reçu ce lien. Vous devrez vérifier cette adresse avant de rattacher votre demande.</p>
                <div class="flex flex-col gap-3">
                    <x-tk.button :href="route('login')" class="w-full">Se connecter</x-tk.button>
                    <x-tk.button :href="route('register')" variant="secondary" class="w-full">Créer un compte</x-tk.button>
                </div>
            @elseif ($state === 'ready')
                <p>Votre adresse email est vérifiée. Confirmez le rattachement pour consulter cette demande et son devis dans votre espace.</p>
                <form method="POST" action="{{ request()->fullUrl() }}">
                    @csrf
                    <x-tk.button type="submit" class="w-full whitespace-normal">Rattacher ma demande</x-tk.button>
                </form>
            @elseif ($state === 'unverified')
                <p role="alert">Vérifiez l’adresse email de votre compte avant de rattacher cette demande.</p>
                <p>Demandez un email de vérification, ouvrez son lien, puis confirmez le rattachement. Aucun rattachement n’a encore été effectué.</p>
                <form method="POST" action="{{ request()->fullUrl() }}">
                    @csrf
                    <input type="hidden" name="intent" value="verify">
                    <x-tk.button type="submit" class="w-full whitespace-normal">Envoyer l’email de vérification</x-tk.button>
                </form>
            @elseif ($state === 'wrong-account')
                <p role="alert">Ce compte ne permet pas de récupérer cette demande. Déconnectez-vous, puis utilisez le compte associé à l’adresse qui a reçu le lien.</p>
                <p>Votre tentative n’a pas utilisé le lien.</p>
                @auth
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-tk.button type="submit" variant="secondary" class="w-full">Se déconnecter</x-tk.button>
                    </form>
                    <p>Après la déconnexion, ouvrez à nouveau le lien reçu par email.</p>
                @endauth
            @elseif ($state === 'used')
                <p role="alert">Ce lien a déjà été utilisé. Si vous avez rattaché votre demande, retrouvez-la dans votre espace client.</p>
                <x-tk.button :href="route('app.requests')" class="w-full">Mes demandes</x-tk.button>
            @else
                <p role="alert">Ce lien est invalide, expiré ou ne permet plus de récupérer une demande. Aucune donnée n’a été modifiée.</p>
            @endif
            <p><a href="{{ route('request-recovery.renew') }}" class="font-medium underline underline-offset-2">Demander un nouveau lien</a></p>
            <p class="text-muted">Demande sans adresse email ou besoin d’aide ? <a href="{{ route('contact') }}" class="font-medium text-content underline underline-offset-2">Contactez l’équipe</a> avec votre référence. L’équipe vérifiera votre identité.</p>
        </div>
    </div>
</x-layouts.auth>
