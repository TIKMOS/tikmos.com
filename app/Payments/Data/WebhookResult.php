<?php

declare(strict_types=1);

namespace App\Payments\Data;

/**
 * Événement webhook normalisé, après vérification de signature.
 */
final readonly class WebhookResult
{
    public function __construct(
        public bool $valid,
        public ?string $reference = null,
        public ?string $providerReference = null,
    ) {}
}
