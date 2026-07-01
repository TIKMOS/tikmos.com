<?php

declare(strict_types=1);

namespace App\Payments;

use App\Payments\Contracts\PaymentGateway;
use App\Payments\Enums\PaymentProvider;
use App\Payments\Exceptions\PaymentException;
use App\Payments\Gateways\CodGateway;
use App\Payments\Gateways\KkiapayGateway;

/**
 * Fabrique/registre des passerelles de paiement.
 *
 * Point d'extension unique : pour ajouter une passerelle (Stripe, FedaPay…),
 * on enregistre un builder via extend() ou on ajoute un case ici.
 */
final class PaymentManager
{
    /** @var array<string, callable(array<string, mixed>): PaymentGateway> */
    private array $customBuilders = [];

    /** @var array<string, PaymentGateway> */
    private array $resolved = [];

    /** @param array<string, mixed> $config config('payments') */
    public function __construct(private array $config) {}

    /**
     * Passerelle du mode de paiement effectif selon les réglages.
     */
    public function gateway(PaymentProvider $provider): PaymentGateway
    {
        return $this->resolved[$provider->value] ??= $this->build($provider);
    }

    /**
     * Passerelle par défaut (déduit du mode configuré : online → kkiapay, sinon cod).
     */
    public function default(): PaymentGateway
    {
        return $this->gateway($this->defaultProvider());
    }

    public function defaultProvider(): PaymentProvider
    {
        return ($this->config['default_mode'] ?? 'cod') === 'online'
            ? PaymentProvider::Kkiapay
            : PaymentProvider::Cod;
    }

    /**
     * Enregistre une passerelle personnalisée (portabilité / extensions projet).
     *
     * @param  callable(array<string, mixed>): PaymentGateway  $builder
     */
    public function extend(string $provider, callable $builder): void
    {
        $this->customBuilders[$provider] = $builder;
        unset($this->resolved[$provider]);
    }

    private function build(PaymentProvider $provider): PaymentGateway
    {
        /** @var array<string, mixed> $gwConfig */
        $gwConfig = $this->config['gateways'][$provider->value] ?? [];

        if (isset($this->customBuilders[$provider->value])) {
            return ($this->customBuilders[$provider->value])($gwConfig);
        }

        return match ($provider) {
            PaymentProvider::Cod => new CodGateway,
            PaymentProvider::Kkiapay => new KkiapayGateway($gwConfig),
        };
    }

    /**
     * Résout une passerelle depuis une valeur brute (webhook /{provider}).
     */
    public function resolveOrFail(string $provider): PaymentGateway
    {
        $enum = PaymentProvider::tryFrom($provider)
            ?? throw new PaymentException("Passerelle de paiement inconnue : {$provider}");

        return $this->gateway($enum);
    }
}
