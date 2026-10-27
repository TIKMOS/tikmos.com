<?php

use App\Enums\Publication;
use App\Enums\ServiceRequestStatus;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Notifications\ServiceRequestReceived;
use App\Notifications\ServiceRequestSubmitted;
use App\Services\PublicFormSubmissionLimiter;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')] class extends Component
{
    public Service $service;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|email|max:255')]
    public string $email = '';

    #[Validate('nullable|string|max:40')]
    public string $phone = '';

    #[Validate('nullable|string|max:255')]
    public string $company = '';

    #[Validate('required|string|max:3000')]
    public string $message = '';

    #[Validate('nullable|string|max:60')]
    public string $budget_hint = '';

    // Anti-spam (honeypot) — doit rester vide.
    public string $website = '';

    public ?string $reference = null;

    public function mount(Service $service): void
    {
        abort_unless($service->status === Publication::Published, 404);
        $this->service = $service;

        if ($user = Auth::user()) {
            $this->name = $user->name;
            $this->email = $user->email;
        }
    }

    public function submit(): void
    {
        $this->reference = null;
        $this->resetValidation('submission');
        $this->validate();

        // Honeypot : un bot remplit ce champ caché.
        if (trim($this->website) !== '') {
            return;
        }

        if (trim($this->phone) === '' && trim($this->email) === '') {
            $this->addError('phone', 'Indiquez au moins un téléphone ou un email pour vous recontacter.');

            return;
        }

        app(PublicFormSubmissionLimiter::class)->ensureAllowed(request(), $this->email, $this->phone);

        $request = ServiceRequest::create([
            'user_id' => Auth::id(),
            'service_id' => $this->service->id,
            'name' => $this->name,
            'email' => $this->email ?: null,
            'phone' => $this->phone ?: null,
            'company' => $this->company ?: null,
            'message' => $this->message,
            'budget_hint' => $this->budget_hint ?: null,
            'status' => ServiceRequestStatus::Sent,
        ]);

        $request->events()->create([
            'from_status' => null,
            'to_status' => ServiceRequestStatus::Sent->value,
            'note' => 'Demande créée depuis la fiche service.',
            'user_id' => Auth::id(),
        ]);

        // Notifications : alerte équipe + accusé de réception client.
        $teamEmail = app(GeneralSettings::class)->emails['contact'] ?? null;
        if ($teamEmail) {
            Notification::route('mail', $teamEmail)->notify(new ServiceRequestReceived($request));
        }
        if ($request->email) {
            Notification::route('mail', $request->email)->notify(new ServiceRequestSubmitted($request));
        }

        $this->reference = $request->reference;
        $this->reset(['phone', 'company', 'message', 'budget_hint']);
        $this->dispatch('tk-toast', type: 'success', message: 'Demande envoyée. Référence : '.$request->reference);
    }
}; ?>

<div>
    @push('meta')
        <x-tk.seo :title="$service->seo_title ?: $service->title" :description="$service->seo_description ?: $service->excerpt" />
    @endpush

    <x-tk.section>
        <a href="{{ route('services') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm text-muted hover:text-content"><x-lucide-arrow-left class="h-4 w-4" /> Tous les services</a>

        <div class="mt-6 grid gap-10 lg:grid-cols-5 lg:gap-12">
            {{-- Détail service --}}
            <div class="lg:col-span-3 reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
                <p class="font-mono text-xs uppercase tracking-[0.18em] text-gold-600">{{ $service->category->label() }}</p>
                <h1 class="mt-2 font-display text-4xl font-black leading-[1.05] tracking-[-0.02em] text-content md:text-5xl">{{ $service->title }}</h1>
                @if ($service->price_label)
                    <p class="mt-4"><x-tk.badge tone="neutral">{{ $service->price_label }}</x-tk.badge></p>
                @endif
                @if ($service->imageUrl())
                    <div class="mt-6 aspect-[16/9] overflow-hidden rounded-card bg-tk-100">
                        <img src="{{ $service->imageUrl() }}" alt="{{ $service->title }}" class="h-full w-full object-cover">
                    </div>
                @endif
                <div class="prose prose-tk mt-6 max-w-none text-content/80">{!! $service->body !!}</div>
            </div>

            {{-- Formulaire de devis --}}
            <div class="lg:col-span-2 reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
                <div class="sticky top-24 rounded-[24px] border border-line bg-surface p-6 shadow-lg" id="devis">
                    <x-tk.kicker>Demander un devis</x-tk.kicker>
                    <p class="mt-2 text-sm text-muted">Pour <strong class="text-content">{{ $service->title }}</strong>. Réponse rapide, sans engagement.</p>

                    @if ($reference)
                        <div class="mt-5 rounded-tile bg-success-soft px-4 py-3 text-sm text-success-ink">
                            <p class="flex items-center gap-2 font-medium"><x-lucide-circle-check class="h-5 w-5" /> Demande envoyée !</p>
                            <p class="mt-1">Votre référence : <span class="font-mono font-bold">{{ $reference }}</span></p>
                            <p class="mt-2">Si vous avez indiqué un email, utilisez le lien reçu pour retrouver votre demande. Sans compte, vous devrez créer un compte et vérifier cette adresse.</p>
                            <a href="{{ route('request-recovery.renew') }}" class="mt-2 inline-block font-medium underline">Recevoir un nouveau lien</a>
                        </div>
                    @endif

                    <form wire:submit="submit" class="mt-5 space-y-4">
                        {{-- Honeypot --}}
                        <input type="text" wire:model="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true" />

                        <x-tk.field label="Nom complet" for="name" :error="$errors->first('name')">
                            <x-tk.input wire:model="name" id="name" icon="user" placeholder="Votre nom" :invalid="$errors->has('name')" />
                        </x-tk.field>
                        <x-tk.field label="Email" for="email" :error="$errors->first('email')">
                            <x-tk.input wire:model="email" id="email" type="email" icon="mail" placeholder="vous@exemple.com" :invalid="$errors->has('email')" />
                        </x-tk.field>
                        <x-tk.field label="Téléphone / WhatsApp" :error="$errors->first('phone')">
                            <x-tk.phone wire:model="phone" :invalid="$errors->has('phone')" />
                        </x-tk.field>
                        <x-tk.field label="Entreprise (optionnel)" for="company">
                            <x-tk.input wire:model="company" id="company" icon="building-2" placeholder="Votre société" />
                        </x-tk.field>
                        <x-tk.field label="Votre besoin" for="message" :error="$errors->first('message')">
                            <textarea wire:model="message" id="message" rows="4" placeholder="Décrivez votre projet ou votre besoin…"
                                      class="w-full rounded-input border bg-surface px-4 py-3 text-[15px] text-content placeholder:text-tk-400 transition-colors focus:outline-none focus:ring-4 focus:ring-ink/10 {{ $errors->has('message') ? 'border-danger' : 'border-tk-300 focus:border-ink' }}"></textarea>
                        </x-tk.field>
                        <x-tk.field label="Budget indicatif (optionnel)" for="budget_hint">
                            <x-tk.input wire:model="budget_hint" id="budget_hint" icon="wallet" placeholder="ex. 500k – 2M XOF" />
                        </x-tk.field>

                        <x-tk.submission-error />

                        <x-tk.button type="submit" variant="primary" size="lg" icon="send" class="w-full" wire:loading.attr="disabled" wire:target="submit">
                            <span wire:loading.remove wire:target="submit">Envoyer ma demande</span>
                            <span wire:loading wire:target="submit">Envoi…</span>
                        </x-tk.button>
                        <p class="text-center text-xs text-muted">Vos informations restent confidentielles.</p>
                    </form>
                </div>
            </div>
        </div>
    </x-tk.section>
</div>
