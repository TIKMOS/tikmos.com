<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ServiceRequest;
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
            ->action('Voir mon devis', route('app.requests.show', $this->request->reference))
            ->line('Vous pouvez l\'accepter ou le refuser depuis votre espace.');
    }
}
