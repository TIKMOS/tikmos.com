<?php

use App\Enums\Publication;
use App\Models\Service;
use App\Services\PublicFormSubmissionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')] class extends Component
{
    #[Locked]
    public ?Service $service = null;

    public string $serviceId = '';

    #[Locked]
    public string $submissionKey = '';

    #[Locked]
    public ?int $submittedUserId = null;

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

    #[Locked]
    public ?string $reference = null;

    public function mount(?Service $service = null): void
    {
        $this->service = $service?->exists ? $service : null;
        abort_if($this->service && $this->service->status !== Publication::Published, 404);
        $this->submissionKey = (string) Str::uuid();

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

        if ($this->service) {
            $serviceId = $this->service->id;
        } else {
            $this->validate(['serviceId' => ['nullable', 'integer', Rule::exists('services', 'id')->where('status', Publication::Published->value)]]);
            $serviceId = $this->serviceId === '' ? null : (int) $this->serviceId;
        }
        $request = app(PublicFormSubmissionService::class)->quote($this->submissionKey, [
            'name' => $this->name, 'email' => $this->email, 'phone' => $this->phone,
            'company' => $this->company, 'message' => $this->message, 'budget_hint' => $this->budget_hint,
        ], $serviceId);
        $this->submittedUserId = $request->user_id;
        $this->submissionKey = (string) Str::uuid();

        $this->reference = $request->reference;
        $this->reset(['phone', 'company', 'message', 'budget_hint']);
        $this->dispatch('tk-toast', type: 'success', message: 'Demande envoyée. Référence : '.$request->reference);
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        return ['availableServices' => $this->service ? collect() : Service::where('status', Publication::Published)->orderBy('title')->get()];
    }
}; ?>

<div>
    @push('meta')
        <x-tk.seo :title="$service ? ($service->seo_title ?: $service->title) : 'Demander un devis'" :description="$service ? ($service->seo_description ?: $service->excerpt) : 'Choisissez un service ou décrivez votre besoin sur mesure. Recevez une référence pour suivre votre demande.'" />
    @endpush

    <x-tk.section>
        <a href="{{ route('services') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm text-muted hover:text-content"><x-lucide-arrow-left class="h-4 w-4" /> Tous les services</a>

        @unless ($service)
            <div class="mt-6 max-w-2xl">
                <h1 class="font-display text-4xl font-black tracking-tight text-content">Demander un devis</h1>
                <p class="mt-4 text-muted">Choisissez un service ou décrivez un besoin sur mesure. Votre demande sera transmise à notre équipe avec une référence de suivi.</p>
                <a href="{{ route('contact') }}" wire:navigate class="mt-3 inline-block text-sm font-medium text-content underline">Pour une question générale : nous contacter</a>
            </div>
        @endunless
        <div class="mt-6 grid gap-10 {{ $service ? 'lg:grid-cols-5 lg:gap-12' : 'mx-auto max-w-2xl' }}">
            @if ($service)
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

            @endif
            {{-- Formulaire de devis --}}
            <div class="{{ $service ? 'lg:col-span-2' : '' }} reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
                <div class="sticky top-24 rounded-[24px] border border-line bg-surface p-6 shadow-lg" id="devis">
                    <x-tk.kicker>Demander un devis</x-tk.kicker>
                    @if ($service)
                        <p class="mt-2 text-sm text-muted">Pour <strong class="text-content">{{ $service->title }}</strong>. Réponse rapide, sans engagement.</p>
                    @endif

                    @if ($reference)
                        <div role="status" tabindex="-1" x-data x-init="$el.focus()" class="mt-5 rounded-tile bg-success-soft px-4 py-3 text-sm text-success-ink">
                            <p class="flex items-center gap-2 font-medium"><x-lucide-circle-check class="h-5 w-5" /> Demande envoyée !</p>
                            <p class="mt-1">Votre référence : <span class="font-mono font-bold">{{ $reference }}</span></p>
                            @if ($submittedUserId && auth()->id() === $submittedUserId)
                                <p class="mt-2">Notre équipe étudiera votre besoin. Retrouvez les prochaines étapes dans votre espace client.</p>
                                <a href="{{ route('app.requests.show', $reference) }}" wire:navigate class="mt-2 inline-block font-medium underline">Suivre ma demande</a>
                            @else
                                <p class="mt-2">Avec un email, utilisez le lien reçu pour retrouver votre demande : connexion ou inscription, vérification de cette adresse, puis confirmation du rattachement. Avec un téléphone seul, notre équipe vous recontactera ; conservez votre référence.</p>
                                <a href="{{ route('request-recovery.renew') }}" class="mt-2 inline-block font-medium underline">Recevoir un nouveau lien par email</a>
                            @endif
                        </div>
                    @endif

                    <form wire:submit="submit" class="mt-5 space-y-4">
                        @unless ($service)
                            <x-tk.field label="Service souhaité" for="service-choice" :error="$errors->first('serviceId')">
                                <select id="service-choice" wire:model="serviceId" aria-invalid="{{ $errors->has('serviceId') ? 'true' : 'false' }}" class="min-h-11 w-full rounded-input border border-tk-300 bg-surface px-3 text-content focus:ring-2 focus:ring-ink">
                                    <option value="">Besoin sur mesure / Je ne sais pas encore</option>
                                    @foreach ($availableServices as $option)
                                        <option value="{{ $option->id }}">{{ $option->title }}</option>
                                    @endforeach
                                </select>
                            </x-tk.field>
                        @endunless
                        {{-- Honeypot --}}
                        <input type="text" wire:model="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true" />

                        <x-tk.field label="Nom complet" for="name" :error="$errors->first('name')">
                            <x-tk.input wire:model="name" id="name" icon="user" placeholder="Votre nom" :invalid="$errors->has('name')" />
                        </x-tk.field>
                        <x-tk.field label="Email" for="email" :error="$errors->first('email')">
                            <x-tk.input wire:model="email" id="email" type="email" icon="mail" placeholder="vous@exemple.com" :invalid="$errors->has('email')" />
                        </x-tk.field>
                        <x-tk.field label="Téléphone / WhatsApp" for="quote-phone" :error="$errors->first('phone')">
                            <x-tk.phone id="quote-phone" wire:model="phone" :invalid="$errors->has('phone')" />
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
