@error('submission')
    <div role="alert" tabindex="-1" x-data x-init="$el.focus()"
         {{ $attributes->class(['rounded-card bg-danger-soft p-4 text-sm text-danger-ink focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-danger-ink']) }}>
        <p class="font-semibold">Envoi temporairement suspendu</p>
        <p class="mt-1">{{ $message }}</p>
    </div>
@enderror
