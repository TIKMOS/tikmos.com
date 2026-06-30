<x-layouts.public>
    <x-slot:head>
        <x-tk.seo title="Achat & commande en France" description="Commandez sur Amazon, Zara, Shein, Temu, Fnac… SEZY achète pour vous et expédie vers Cotonou. Paiement local : MoMo, Wero, Lemfi." />
    </x-slot:head>

    {{-- Hero --}}
    <section class="bg-paper">
        <div class="mx-auto grid w-full max-w-6xl items-center gap-10 px-5 py-14 sm:px-6 md:py-20 lg:grid-cols-2 lg:px-8">
            <div class="reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
                <x-tk.kicker>Achat &amp; commande</x-tk.kicker>
                <h1 class="mt-4 font-display text-4xl font-black leading-[1] tracking-[-0.02em] text-content md:text-5xl">Votre shopping en France,<br>sans limites.</h1>
                <p class="mt-5 max-w-md text-lg text-muted">Passez vos commandes sur tous les sites en France. SEZY s'occupe de l'achat et de l'expédition vers Cotonou.</p>
                <div class="mt-7 flex flex-wrap gap-3">
                    <x-tk.button href="{{ route('register') }}" variant="primary" size="lg" icon="user-plus">Créer un compte</x-tk.button>
                    <x-tk.whatsapp-button :floating="false" message="Bonjour SEZY, je veux passer une commande." />
                </div>
                <div class="mt-7 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-muted">
                    <span class="inline-flex items-center gap-1.5"><x-lucide-shield-check class="h-4 w-4 text-content" /> Assurance incluse</span>
                    <span class="inline-flex items-center gap-1.5"><x-lucide-wallet class="h-4 w-4 text-content" /> Paiement local sécurisé</span>
                    <span class="inline-flex items-center gap-1.5"><x-lucide-smile class="h-4 w-4 text-content" /> Zéro stress</span>
                </div>
            </div>
            <div class="reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
                <div class="aspect-[5/4] overflow-hidden rounded-[28px] bg-tk-100 shadow-lg">
                    <img src="https://images.unsplash.com/photo-1607082349566-187342175e2f?w=900&q=70&auto=format&fit=crop" alt="Shopping en ligne" loading="eager" class="h-full w-full object-cover" />
                </div>
            </div>
        </div>
    </section>

    {{-- Marques --}}
    <div class="border-y border-line bg-canvas py-6">
        <div class="mx-auto max-w-6xl px-5">
            <p class="mb-3 text-center text-xs font-medium uppercase tracking-[0.18em] text-tk-400">On achète pour vous sur vos sites préférés</p>
            @php($prefSites = \App\Models\PreferredSite::active()->get())
            <div class="relative overflow-hidden [mask-image:linear-gradient(90deg,transparent,#000_12%,#000_88%,transparent)]">
                <div class="tk-marquee">
                    @for ($k = 0; $k < 2; $k++)
                        @if ($prefSites->isNotEmpty())
                            <div class="flex shrink-0 items-center gap-10 pr-10" aria-hidden="{{ $k ? 'true' : 'false' }}">
                                @foreach ($prefSites as $site)
                                    @php($logo = $site->logoUrl())
                                    @if ($logo)
                                        <img src="{{ $logo }}" alt="{{ $site->name }}" class="h-7 w-auto shrink-0 opacity-60 grayscale transition hover:opacity-100 hover:grayscale-0">
                                    @else
                                        <span class="shrink-0 font-display text-xl font-bold text-tk-400">{{ $site->name }}</span>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <div class="flex shrink-0 items-center gap-12 pr-12 font-display text-xl font-bold text-tk-400" aria-hidden="{{ $k ? 'true' : 'false' }}">
                                <span>amazon</span><span>ZARA</span><span>SHEIN</span><span>Temu</span><span>Leroy&nbsp;Merlin</span><span>fnac</span><span>AliExpress</span><span>Cdiscount</span>
                            </div>
                        @endif
                    @endfor
                </div>
            </div>
        </div>
    </div>

    {{-- 3 étapes --}}
    <x-tk.section>
        <div class="max-w-2xl reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
            <x-tk.kicker>Comment ça marche</x-tk.kicker>
            <h2 class="mt-3 font-display text-3xl font-extrabold text-content md:text-4xl">Trois étapes, zéro stress.</h2>
        </div>
        @php($tints = ['bg-sky', 'bg-mint', 'bg-lilac'])
        <div class="mt-10 grid gap-5 md:grid-cols-3">
            @foreach ([
                ['n' => '01', 'icon' => 'mouse-pointer-click', 'title' => 'Choisissez', 'desc' => 'Le produit de vos rêves sur votre site préféré (Amazon, Zara, Shein, Temu, Fnac…).'],
                ['n' => '02', 'icon' => 'wallet', 'title' => 'Confiez-nous l\'achat', 'desc' => 'Envoyez-nous le lien. Payez en local : Mobile Money (MoMo), Wero, Lemfi.'],
                ['n' => '03', 'icon' => 'package-check', 'title' => 'Nous expédions', 'desc' => 'SEZY achète, réceptionne et expédie votre colis vers Cotonou. Vous suivez chaque étape.'],
            ] as $i => $step)
                <div class="reveal rounded-card border border-line/60 {{ $tints[$i % 3] }} p-7" x-data x-intersect.once="setTimeout(() => $el.classList.add('is-revealed'), {{ $i * 100 }})">
                    <div class="flex items-center justify-between">
                        <span class="grid h-11 w-11 place-items-center rounded-tile bg-ink text-paper"><x-dynamic-component :component="'lucide-'.$step['icon']" class="h-5 w-5" /></span>
                        <span class="font-display text-4xl font-black text-content/15">{{ $step['n'] }}</span>
                    </div>
                    <h3 class="mt-4 font-display text-xl font-bold text-content">{{ $step['title'] }}</h3>
                    <p class="mt-2 text-muted">{{ $step['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </x-tk.section>

    {{-- Paiement local --}}
    <x-tk.section tone="canvas">
        <div class="grid items-center gap-8 lg:grid-cols-2">
            <div class="reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
                <x-tk.kicker>Paiement local sécurisé</x-tk.kicker>
                <h2 class="mt-3 font-display text-3xl font-extrabold text-content">Payez comme chez vous.</h2>
                <p class="mt-3 max-w-md text-muted">Pas besoin de carte internationale. Réglez vos achats avec les moyens que vous utilisez tous les jours.</p>
                <div class="mt-5 flex flex-wrap gap-2">
                    @foreach (['Mobile Money', 'MTN MoMo', 'Moov Money', 'Wero', 'Lemfi'] as $pay)
                        <span class="rounded-pill border border-line bg-surface px-4 py-2 text-sm font-medium text-content">{{ $pay }}</span>
                    @endforeach
                </div>
            </div>
            <div class="reveal grid grid-cols-2 gap-4" x-data x-intersect.once="$el.classList.add('is-revealed')">
                <div class="rounded-card border border-line/60 bg-peach p-5"><x-lucide-shield-check class="h-7 w-7 text-content" /><p class="mt-3 font-display font-bold text-content">Assurance incluse</p><p class="mt-1 text-sm text-muted">Vos colis voyagent l'esprit tranquille.</p></div>
                <div class="rounded-card border border-line/60 bg-sky p-5"><x-lucide-timer class="h-7 w-7 text-content" /><p class="mt-3 font-display font-bold text-content">3–5 jours</p><p class="mt-1 text-sm text-muted">Délai Paris → Cotonou.</p></div>
                <div class="rounded-card border border-line/60 bg-mint p-5"><x-lucide-route class="h-7 w-7 text-content" /><p class="mt-3 font-display font-bold text-content">Suivi à la trace</p><p class="mt-1 text-sm text-muted">Du paiement à la livraison.</p></div>
                <div class="rounded-card border border-line/60 bg-lilac p-5"><x-lucide-headset class="h-7 w-7 text-content" /><p class="mt-3 font-display font-bold text-content">Support réactif</p><p class="mt-1 text-sm text-muted">Une équipe dispo sur WhatsApp.</p></div>
            </div>
        </div>
        <div class="mt-10 flex flex-wrap justify-center gap-3">
            <x-tk.button href="{{ route('register') }}" variant="primary" size="lg" icon="user-plus">Passer ma commande</x-tk.button>
            <x-tk.whatsapp-button :floating="false" message="Bonjour SEZY, je veux passer une commande." />
        </div>
    </x-tk.section>
</x-layouts.public>
