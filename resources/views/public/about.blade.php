@php
    $personSchemaJson = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'ProfilePage',
        'url' => route('about'),
        'name' => $aboutSettings->seo_title,
        'mainEntity' => [
            '@type' => 'Person',
            '@id' => route('about').'#person',
            'name' => $aboutSettings->person_name,
            'jobTitle' => $aboutSettings->professor_label.' et '.$aboutSettings->specialty_label,
            'url' => route('about'),
            'sameAs' => [$aboutSettings->linkedin_url, url('/')],
            'worksFor' => ['@type' => 'Organization', 'name' => 'Tikmos'],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

    $aboutImage = $aboutSettings->image
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($aboutSettings->image)
        : asset('img/about-illustration.svg');
    $quoteHref = str_starts_with($aboutSettings->quote_url, '/') ? url($aboutSettings->quote_url) : $aboutSettings->quote_url;
    $servicesHref = str_starts_with($aboutSettings->services_url, '/') ? url($aboutSettings->services_url) : $aboutSettings->services_url;
    $quoteExternal = ! str_starts_with($aboutSettings->quote_url, '/');
    $servicesExternal = ! str_starts_with($aboutSettings->services_url, '/');
    $tints = ['bg-indigo-50', 'bg-gold-100', 'bg-sand', 'bg-mist'];
@endphp

<x-layouts.public>
    <x-slot:head>
        <x-tk.seo :title-exact="$aboutSettings->seo_title" :description="$aboutSettings->seo_description" :image="$aboutSettings->og_image ? \Illuminate\Support\Facades\Storage::disk('public')->url($aboutSettings->og_image) : null" :canonical="route('about')" />
        <script type="application/ld+json">{!! $personSchemaJson !!}</script>
    </x-slot:head>

    @if ($aboutSettings->show_hero)
        <section class="bg-paper">
            <div class="mx-auto grid w-full max-w-6xl items-center gap-10 px-5 py-14 sm:px-6 md:py-20 lg:grid-cols-2 lg:px-8">
                <div class="reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
                    <x-tk.kicker>{{ $aboutSettings->hero_kicker }}</x-tk.kicker>
                    <x-tk.divider-gold class="mt-3" />
                    <h1 class="mt-4 font-display text-4xl font-black leading-[1] tracking-[-0.02em] text-content md:text-5xl">{{ $aboutSettings->title }}</h1>
                    <p class="mt-5 max-w-md text-lg text-muted">{{ $aboutSettings->subtitle }}</p>
                    <div class="mt-7 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-muted">
                        <span class="inline-flex items-center gap-1.5"><x-lucide-graduation-cap class="h-4 w-4 text-gold-600" /> {{ $aboutSettings->professor_label }}</span>
                        <span class="text-tk-300" aria-hidden="true">·</span>
                        <span class="inline-flex items-center gap-1.5"><x-lucide-cpu class="h-4 w-4 text-gold-600" /> {{ $aboutSettings->specialty_label }}</span>
                    </div>
                    @if ($aboutSettings->show_linkedin)
                        <a href="{{ $aboutSettings->linkedin_url }}" target="_blank" rel="noopener noreferrer" class="mt-6 inline-flex min-h-11 items-center gap-2 rounded-btn border border-line bg-surface px-4 py-2 text-sm font-semibold text-indigo-700 transition hover:border-indigo-700 hover:bg-indigo-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700">
                            <span>{{ $aboutSettings->linkedin_label }}</span><span class="sr-only"> (nouvel onglet)</span>
                            <x-lucide-arrow-up-right class="h-4 w-4" aria-hidden="true" />
                        </a>
                    @endif
                </div>
                <div class="reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
                    <div class="aspect-[5/4] overflow-hidden rounded-[28px] border border-line bg-surface shadow-lg">
                        <img src="{{ $aboutImage }}" alt="{{ $aboutSettings->image_alt }}" loading="eager" class="h-full w-full object-cover" />
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if ($aboutSettings->show_story)
        <x-tk.section tone="canvas">
            <div class="mx-auto max-w-3xl reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
                <x-tk.kicker>{{ $aboutSettings->story_kicker }}</x-tk.kicker>
                <x-tk.divider-gold class="mt-3" />
                <div class="mt-5 space-y-5 text-lg leading-relaxed text-content/80">
                    @foreach ($aboutParagraphs as $paragraph)
                        <p>{{ $paragraph->body }}</p>
                    @endforeach
                    @if ($aboutSettings->show_linkedin)
                        <p><a href="{{ $aboutSettings->linkedin_url }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-indigo-700 underline decoration-indigo-300 underline-offset-4 hover:text-indigo-900 focus-visible:rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700">Retrouver {{ $aboutSettings->person_name }} sur LinkedIn<span class="sr-only"> (nouvel onglet)</span></a></p>
                    @endif
                </div>
            </div>
        </x-tk.section>
    @endif

    @if ($aboutSettings->show_values)
        <x-tk.section>
            <div class="max-w-2xl reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
                <x-tk.kicker>{{ $aboutSettings->values_kicker }}</x-tk.kicker>
                <x-tk.divider-gold class="mt-3" />
                <h2 class="mt-3 font-display text-3xl font-extrabold text-content md:text-4xl">{{ $aboutSettings->values_title }}</h2>
            </div>
            <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($aboutValues as $index => $value)
                    <div class="reveal rounded-card border border-line/60 {{ $tints[$index % count($tints)] }} p-6" x-data x-intersect.once="setTimeout(() => $el.classList.add('is-revealed'), {{ $index * 80 }})">
                    @php($safeIcon = in_array($value->icon, ['target', 'lightbulb', 'rocket', 'gem', 'book-open', 'brain', 'check', 'heart'], true) ? $value->icon : 'target')
                    <span class="grid h-12 w-12 place-items-center rounded-tile bg-ink text-paper"><x-dynamic-component :component="'lucide-'.$safeIcon" class="h-6 w-6" aria-hidden="true" /></span>
                        <h3 class="mt-4 font-display text-lg font-bold text-content">{{ $value->title }}</h3>
                        <p class="mt-1.5 text-sm text-muted">{{ $value->description }}</p>
                    </div>
                @endforeach
            </div>
        </x-tk.section>
    @endif

    @if ($aboutSettings->show_cta)
        <x-tk.section tone="canvas">
            <div class="flex flex-col items-center text-center reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
                <x-tk.divider-gold />
                <h2 class="mt-5 font-display text-3xl font-extrabold text-content md:text-4xl">{{ $aboutSettings->cta_title }}</h2>
                <div class="mt-7 flex flex-wrap justify-center gap-3">
                    @if ($quoteExternal)
                        <a href="{{ $quoteHref }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-[50px] select-none items-center justify-center gap-2 whitespace-nowrap rounded-btn bg-ink px-6 text-base font-medium text-paper transition hover:bg-tk-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"><x-lucide-file-text class="h-5 w-5" aria-hidden="true" /><span>{{ $aboutSettings->quote_label }}</span><span class="sr-only"> (nouvel onglet)</span></a>
                    @else
                        <x-tk.button :href="$quoteHref" variant="primary" size="lg" icon="file-text">{{ $aboutSettings->quote_label }}</x-tk.button>
                    @endif
                    @if ($servicesExternal)
                        <a href="{{ $servicesHref }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-[50px] select-none items-center justify-center gap-2 whitespace-nowrap rounded-btn border border-gold bg-paper px-6 text-base font-medium text-gold-ink transition hover:bg-gold-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"><span>{{ $aboutSettings->services_label }}</span><span class="sr-only"> (nouvel onglet)</span></a>
                    @else
                        <x-tk.button :href="$servicesHref" variant="gold" size="lg">{{ $aboutSettings->services_label }}</x-tk.button>
                    @endif
                </div>
            </div>
        </x-tk.section>
    @endif
</x-layouts.public>
