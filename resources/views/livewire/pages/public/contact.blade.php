<?php

use App\Services\PublicFormSubmissionService;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')] class extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:30')]
    public string $phone = '';

    #[Validate('nullable|email|max:255')]
    public string $email = '';

    #[Validate('required|string|max:40')]
    public string $subject = 'Question générale';

    #[Validate('required|string|max:3000')]
    public string $message = '';

    public bool $sent = false;

    #[Locked]
    public string $submissionKey = '';

    public function mount(): void
    {
        $this->submissionKey = (string) Str::uuid();
    }

    /** @return array<int, string> */
    public function subjects(): array
    {
        return ['Question générale', 'Formation', 'Solution informatique', 'Boutique', 'Autre'];
    }

    public function submit(): void
    {
        $this->sent = false;
        $this->resetValidation('submission');
        $this->validate();

        if (trim($this->phone) === '' && trim($this->email) === '') {
            $this->addError('phone', 'Indiquez au moins un téléphone ou un email.');

            return;
        }

        app(PublicFormSubmissionService::class)->contact($this->submissionKey, [
            'name' => $this->name, 'email' => $this->email, 'phone' => $this->phone,
            'subject' => $this->subject, 'message' => $this->message,
        ]);
        $this->submissionKey = (string) Str::uuid();

        $this->reset(['name', 'phone', 'email', 'message']);
        $this->subject = 'Question générale';
        $this->sent = true;
        $this->dispatch('tk-toast', type: 'success', message: 'Message envoyé. Nous vous répondons très vite.');
    }
}; ?>

<div>
    @push('meta')
        <x-tk.seo title="Contact" description="Contactez Tikmos pour une question générale ou un message concernant nos services et la boutique." />
    @endpush

    <x-tk.section tone="canvas">
        <div class="mx-auto max-w-2xl text-center reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
            <x-tk.kicker class="justify-center">Contact</x-tk.kicker>
            <h1 class="mt-3 font-display text-4xl font-black leading-[1] tracking-[-0.02em] text-content md:text-5xl">Contact général</h1>
            <p class="mx-auto mt-4 max-w-lg text-lg text-muted">Une question, une information ou un message ? Ce formulaire ne crée pas de demande de devis suivie.</p>
        </div>

        <div class="mt-5 text-center"><a href="{{ route('quote-request') }}" wire:navigate class="inline-block min-h-11 font-medium text-content underline underline-offset-2">Pour un projet : demander un devis suivi</a></div>
        <div class="mt-12 grid gap-6 lg:grid-cols-5 lg:gap-8">
            {{-- Colonne infos --}}
            @php($general = app(\App\Settings\GeneralSettings::class))
            <div class="space-y-4 lg:col-span-2 reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
                <a href="https://wa.me/{{ preg_replace('/\D/', '', $general->whatsapp_number) }}" target="_blank" rel="noopener"
                   class="group flex items-center gap-4 rounded-card border border-line/60 bg-sage p-5 transition-all hover:-translate-y-0.5 hover:shadow-pop">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-tile bg-whatsapp text-ink">
                        <svg viewBox="0 0 24 24" class="h-6 w-6" fill="currentColor" aria-hidden="true"><path d="M.057 24l1.687-6.163a11.867 11.867 0 0 1-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.821 11.821 0 0 1 8.413 3.488 11.824 11.824 0 0 1 3.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 0 1-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01a1.092 1.092 0 0 0-.792.372c-.272.297-1.04 1.016-1.04 2.479s1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="font-display font-bold text-content">WhatsApp — réponse rapide</p>
                        <p class="truncate text-sm text-muted">{{ $general->brand_phone }}</p>
                    </div>
                    <x-lucide-arrow-up-right class="ml-auto h-5 w-5 shrink-0 text-content transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
                </a>

                <div class="flex items-start gap-4 rounded-card border border-line/60 bg-mist p-5">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-tile bg-ink text-paper"><x-lucide-phone class="h-5 w-5" /></span>
                    <div class="min-w-0">
                        <p class="font-display font-bold text-content">Téléphone</p>
                        @foreach ($general->phones as $phone)
                            <p class="text-sm text-muted">{{ $phone }}</p>
                        @endforeach
                    </div>
                </div>

                @if (! empty($general->emails['contact']))
                    <div class="flex items-start gap-4 rounded-card border border-line/60 bg-sand p-5">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-tile bg-ink text-paper"><x-lucide-mail class="h-5 w-5" /></span>
                        <div class="min-w-0">
                            <p class="font-display font-bold text-content">Email</p>
                            <a href="mailto:{{ $general->emails['contact'] }}" class="text-sm text-muted hover:text-content">{{ $general->emails['contact'] }}</a>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Carte formulaire --}}
            <div class="lg:col-span-3 reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
                <div class="rounded-[24px] border border-line bg-surface p-6 shadow-lg sm:p-8">
                    @if ($sent)
                        <div class="mb-6 flex items-center gap-2 rounded-tile bg-success-soft px-4 py-3 text-sm font-medium text-success-ink">
                            <x-lucide-circle-check class="h-5 w-5" /> Merci ! Votre message a bien été envoyé.
                        </div>
                    @endif

                    <form wire:submit="submit" class="space-y-5">
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-tk.field label="Nom complet" for="name" :error="$errors->first('name')">
                                <x-tk.input wire:model="name" id="name" icon="user" placeholder="Votre nom" :invalid="$errors->has('name')" />
                            </x-tk.field>
                            <x-tk.field label="Email" for="email" :error="$errors->first('email')">
                                <x-tk.input wire:model="email" id="email" type="email" icon="mail" placeholder="vous@exemple.com" :invalid="$errors->has('email')" />
                            </x-tk.field>
                        </div>

                        <x-tk.field label="Téléphone / WhatsApp" for="contact-phone" :error="$errors->first('phone')">
                            <x-tk.phone id="contact-phone" wire:model="phone" :invalid="$errors->has('phone')" />
                        </x-tk.field>

                        <x-tk.field label="Sujet" for="subject" :error="$errors->first('subject')">
                            <div class="relative">
                                <x-lucide-tag class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-tk-400" />
                                <select wire:model="subject" id="subject"
                                        class="min-h-[48px] w-full appearance-none rounded-input border border-control-line bg-surface pl-11 pr-10 text-[15px] text-content transition-colors focus:border-ink focus:ring-4 focus:ring-ink/10">
                                    @foreach ($this->subjects() as $s)
                                        <option value="{{ $s }}">{{ $s }}</option>
                                    @endforeach
                                </select>
                                <x-lucide-chevron-down class="pointer-events-none absolute right-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-tk-400" />
                            </div>
                        </x-tk.field>

                        <x-tk.field label="Message" for="message" :error="$errors->first('message')">
                            <textarea wire:model="message" id="message" rows="5" placeholder="Comment pouvons-nous vous aider ?"
                                      class="w-full rounded-input border bg-surface px-4 py-3 text-[15px] text-content placeholder:text-muted transition-colors focus:ring-4 focus:ring-ink/10 {{ $errors->has('message') ? 'border-danger' : 'border-control-line focus:border-ink' }}"></textarea>
                        </x-tk.field>

                        <x-tk.submission-error />

                        <x-tk.button type="submit" variant="primary" size="lg" icon="send" class="w-full" wire:loading.attr="disabled" wire:target="submit">
                            <span wire:loading.remove wire:target="submit">Envoyer le message</span>
                            <span wire:loading wire:target="submit">Envoi…</span>
                        </x-tk.button>
                        <p class="text-center text-xs text-muted">Réponse sous 24 h ouvrées. Vos informations restent confidentielles.</p>
                    </form>
                </div>
            </div>
        </div>
    </x-tk.section>
</div>
