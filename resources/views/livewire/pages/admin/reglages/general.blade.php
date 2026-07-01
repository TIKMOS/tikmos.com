<?php

use App\Settings\GeneralSettings;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component
{
    public string $brand_phone = '';

    public string $whatsapp_number = '';

    /** @var array<int, string> */
    public array $phones = [];

    public string $email_contact = '';

    public string $email_support = '';

    /** @var array<int, array{network: string, url: string}> */
    public array $socials = [];

    public bool $top_bandeau_enabled = true;

    public string $top_bandeau_text = '';

    public string $address_cotonou = '';

    public string $address_paris = '';

    public string $business_hours = '';

    public string $footer_tagline = '';

    public int $delivered_base = 0;

    /** @var array<int, array{label: string, path: string, visible: bool}> */
    public array $nav_links = [];

    public function mount(GeneralSettings $s): void
    {
        $this->brand_phone = $s->brand_phone;
        $this->whatsapp_number = $s->whatsapp_number;
        $this->phones = $s->phones;
        $this->email_contact = $s->emails['contact'] ?? '';
        $this->email_support = $s->emails['support'] ?? '';
        $this->socials = $s->socials;
        $this->top_bandeau_enabled = $s->top_bandeau_enabled;
        $this->top_bandeau_text = $s->top_bandeau_text;
        $this->address_cotonou = $s->addresses['cotonou'] ?? '';
        $this->address_paris = $s->addresses['paris'] ?? '';
        $this->business_hours = $s->business_hours;
        $this->footer_tagline = $s->footer_tagline;
        $this->delivered_base = $s->delivered_base;
        $this->nav_links = $s->nav_links;
    }

    public function addPhone(): void
    {
        $this->phones[] = '';
    }

    public function removePhone(int $i): void
    {
        unset($this->phones[$i]);
        $this->phones = array_values($this->phones);
    }

    public function addSocial(): void
    {
        $this->socials[] = ['network' => 'instagram', 'url' => ''];
    }

    public function removeSocial(int $i): void
    {
        unset($this->socials[$i]);
        $this->socials = array_values($this->socials);
    }

    public function addNavLink(): void
    {
        $this->nav_links[] = ['label' => '', 'path' => '', 'visible' => true];
    }

    public function removeNavLink(int $i): void
    {
        unset($this->nav_links[$i]);
        $this->nav_links = array_values($this->nav_links);
    }

    public function save(GeneralSettings $s): void
    {
        $this->validate([
            'brand_phone' => ['required', 'string', 'max:40'],
            'whatsapp_number' => ['required', 'string', 'max:40'],
            'phones' => ['array'],
            'phones.*' => ['nullable', 'string', 'max:40'],
            'email_contact' => ['nullable', 'email', 'max:255'],
            'email_support' => ['nullable', 'email', 'max:255'],
            'socials' => ['array'],
            'socials.*.network' => ['required', 'string', 'max:30'],
            'socials.*.url' => ['nullable', 'string', 'max:255'],
            'top_bandeau_text' => ['required', 'string', 'max:160'],
            'address_cotonou' => ['required', 'string', 'max:255'],
            'address_paris' => ['required', 'string', 'max:255'],
            'business_hours' => ['required', 'string', 'max:120'],
            'footer_tagline' => ['required', 'string', 'max:200'],
            'delivered_base' => ['required', 'integer', 'min:0', 'max:1000000'],
            'nav_links' => ['array'],
            'nav_links.*.label' => ['required', 'string', 'max:40'],
            'nav_links.*.path' => ['required', 'string', 'max:60'],
        ]);

        $s->brand_phone = $this->brand_phone;
        $s->whatsapp_number = $this->whatsapp_number;
        $s->phones = array_values(array_filter($this->phones, fn ($p) => trim((string) $p) !== ''));
        $s->emails = ['contact' => $this->email_contact, 'support' => $this->email_support];
        $s->socials = array_map(fn ($x) => ['network' => $x['network'], 'url' => $x['url']], $this->socials);
        $s->top_bandeau_enabled = $this->top_bandeau_enabled;
        $s->top_bandeau_text = $this->top_bandeau_text;
        $s->addresses = ['cotonou' => $this->address_cotonou, 'paris' => $this->address_paris];
        $s->business_hours = $this->business_hours;
        $s->footer_tagline = $this->footer_tagline;
        $s->delivered_base = $this->delivered_base;
        $s->nav_links = array_map(fn ($x) => [
            'label' => $x['label'], 'path' => ltrim((string) $x['path'], '/'), 'visible' => (bool) ($x['visible'] ?? true),
        ], $this->nav_links);
        $s->save();

        $this->dispatch('tk-toast', type: 'success', message: 'Réglages généraux enregistrés.');
    }
}; ?>

<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.reglages') }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-muted hover:text-content"><x-lucide-arrow-left class="h-4 w-4" /> Réglages</a>
            <h1 class="mt-2 font-display text-3xl font-extrabold text-content">Général</h1>
        </div>
        <x-tk.button wire:click="save" variant="primary" icon="check">
            <span wire:loading.remove wire:target="save">Enregistrer</span>
            <span wire:loading wire:target="save">Enregistrement…</span>
        </x-tk.button>
    </div>

    <form wire:submit="save" class="mt-8 space-y-5">
        {{-- Contacts --}}
        <x-tk.admin-section title="Contacts" description="Numéro principal et WhatsApp affichés sur le site." icon="phone">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-tk.field label="Téléphone principal" :error="$errors->first('brand_phone')"><x-tk.input wire:model="brand_phone" icon="phone" /></x-tk.field>
                <x-tk.field label="Numéro WhatsApp (digits)" hint="Ex. 2290166386417" :error="$errors->first('whatsapp_number')"><x-tk.input wire:model="whatsapp_number" icon="message-circle" /></x-tk.field>
            </div>
            <div class="mt-4">
                <p class="mb-1.5 text-[13px] font-medium text-content">Autres numéros (footer)</p>
                <div class="space-y-2">
                    @foreach ($phones as $i => $phone)
                        <div class="flex items-center gap-2" wire:key="phone-{{ $i }}">
                            <x-tk.input wire:model="phones.{{ $i }}" class="flex-1" />
                            <button type="button" wire:click="removePhone({{ $i }})" class="grid h-10 w-10 shrink-0 place-items-center rounded-tile text-tk-400 hover:bg-tk-100 hover:text-danger"><x-lucide-trash-2 class="h-4 w-4" /></button>
                        </div>
                    @endforeach
                </div>
                <button type="button" wire:click="addPhone" class="mt-2 inline-flex items-center gap-1 text-sm font-medium text-content hover:underline"><x-lucide-plus class="h-4 w-4" /> Ajouter un numéro</button>
            </div>
            <div class="mt-4 grid gap-5 sm:grid-cols-2">
                <x-tk.field label="Email contact" :error="$errors->first('email_contact')"><x-tk.input wire:model="email_contact" type="email" icon="mail" /></x-tk.field>
                <x-tk.field label="Email support" :error="$errors->first('email_support')"><x-tk.input wire:model="email_support" type="email" icon="mail" /></x-tk.field>
            </div>
        </x-tk.admin-section>

        {{-- Bandeau --}}
        <x-tk.admin-section title="Bandeau supérieur" description="La fine bande au-dessus du menu." icon="panel-top">
            <x-tk.toggle label="Afficher le bandeau" wire:model="top_bandeau_enabled" />
            <div class="mt-4">
                <x-tk.field label="Texte du bandeau" :error="$errors->first('top_bandeau_text')"><x-tk.input wire:model="top_bandeau_text" /></x-tk.field>
            </div>
        </x-tk.admin-section>

        {{-- Réseaux --}}
        <x-tk.admin-section title="Réseaux sociaux" description="Liens affichés dans le footer (icône = nom du réseau)." icon="share-2">
            <div class="space-y-2">
                @foreach ($socials as $i => $social)
                    <div class="flex items-center gap-2" wire:key="social-{{ $i }}">
                        <select wire:model="socials.{{ $i }}.network" class="min-h-[48px] w-40 shrink-0 rounded-input border border-tk-300 bg-surface px-3 text-[15px] text-content focus:border-ink focus:outline-none">
                            @foreach (['instagram', 'facebook', 'linkedin', 'youtube', 'twitter'] as $net)<option value="{{ $net }}">{{ ucfirst($net) }}</option>@endforeach
                        </select>
                        <x-tk.input wire:model="socials.{{ $i }}.url" placeholder="https://…" class="flex-1" />
                        <button type="button" wire:click="removeSocial({{ $i }})" class="grid h-10 w-10 shrink-0 place-items-center rounded-tile text-tk-400 hover:bg-tk-100 hover:text-danger"><x-lucide-trash-2 class="h-4 w-4" /></button>
                    </div>
                @endforeach
            </div>
            <button type="button" wire:click="addSocial" class="mt-2 inline-flex items-center gap-1 text-sm font-medium text-content hover:underline"><x-lucide-plus class="h-4 w-4" /> Ajouter un réseau</button>
        </x-tk.admin-section>

        {{-- Adresses & horaires --}}
        <x-tk.admin-section title="Adresses & horaires" icon="map-pin">
            <div class="space-y-4">
                <x-tk.field label="Adresse Cotonou" :error="$errors->first('address_cotonou')"><x-tk.input wire:model="address_cotonou" /></x-tk.field>
                <x-tk.field label="Adresse Paris" :error="$errors->first('address_paris')"><x-tk.input wire:model="address_paris" /></x-tk.field>
                <x-tk.field label="Horaires" :error="$errors->first('business_hours')"><x-tk.input wire:model="business_hours" /></x-tk.field>
            </div>
        </x-tk.admin-section>

        {{-- Footer & compteur --}}
        <x-tk.admin-section title="Footer & compteur" icon="badge-check">
            <div class="space-y-4">
                <x-tk.field label="Slogan du footer" :error="$errors->first('footer_tagline')"><x-tk.input wire:model="footer_tagline" /></x-tk.field>
                <x-tk.field label="Base du compteur de colis livrés" hint="L'affichage = cette base + les colis réellement livrés." :error="$errors->first('delivered_base')">
                    <x-tk.input wire:model="delivered_base" type="number" min="0" icon="package-check" />
                </x-tk.field>
            </div>
        </x-tk.admin-section>

        {{-- Navigation --}}
        <x-tk.admin-section title="Menu de navigation" description="Libellés, chemins et visibilité des liens du menu." icon="menu">
            <div class="space-y-2">
                @foreach ($nav_links as $i => $link)
                    <div class="flex items-center gap-2" wire:key="nav-{{ $i }}">
                        <x-tk.input wire:model="nav_links.{{ $i }}.label" placeholder="Libellé" class="flex-1" />
                        <x-tk.input wire:model="nav_links.{{ $i }}.path" placeholder="chemin (ex. services)" class="flex-1" />
                        <label class="flex shrink-0 items-center gap-1.5 text-xs text-muted"><input type="checkbox" wire:model="nav_links.{{ $i }}.visible" class="h-4 w-4 rounded border-tk-300 text-ink focus:ring-ink/20"> Visible</label>
                        <button type="button" wire:click="removeNavLink({{ $i }})" class="grid h-10 w-10 shrink-0 place-items-center rounded-tile text-tk-400 hover:bg-tk-100 hover:text-danger"><x-lucide-trash-2 class="h-4 w-4" /></button>
                    </div>
                @endforeach
            </div>
            <button type="button" wire:click="addNavLink" class="mt-2 inline-flex items-center gap-1 text-sm font-medium text-content hover:underline"><x-lucide-plus class="h-4 w-4" /> Ajouter un lien</button>
        </x-tk.admin-section>

        <div class="flex justify-end">
            <x-tk.button type="submit" variant="primary" icon="check">Enregistrer les réglages</x-tk.button>
        </div>
    </form>
</div>
