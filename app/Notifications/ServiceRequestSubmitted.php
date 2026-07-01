<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ServiceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Accusé de réception au client qui a soumis une demande. */
class ServiceRequestSubmitted extends Notification implements ShouldQueue
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
        return (new MailMessage)
            ->subject('Votre demande a bien été reçue — '.$this->request->reference)
            ->greeting('Bonjour '.$this->request->name.',')
            ->line('Nous avons bien reçu votre demande de devis. Notre équipe revient vers vous rapidement.')
            ->line('Votre référence : '.$this->request->reference)
            ->line('Merci de votre confiance.');
    }
}
