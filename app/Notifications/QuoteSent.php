<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ServiceRequest;
use App\Services\GuestRequestRecoveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Au client : son devis est prêt. */
class QuoteSent extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ServiceRequest $request) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $quote = $this->request->quote;

        $mail = (new MailMessage)
            ->subject('Votre devis est prêt — '.$this->request->reference)
            ->greeting('Bonjour '.$this->request->name.',')
            ->line('Votre devis est disponible.');

        if ($quote) {
            $mail->line('Montant : '.$quote->amountLabel());
        }

        return $mail
            ->action('Voir mon devis', app(GuestRequestRecoveryService::class)->linkFor($this->request))
            ->line('Connectez-vous avec l’adresse destinataire. Pour une demande sans compte, vérifiez cette adresse et confirmez le rattachement. Le lien de récupération est personnel, valable 24 heures et à usage unique.')
            ->line('Vous pouvez accepter ou refuser un devis encore valide depuis votre espace.');
    }
}
