<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ServiceRequest;
use App\Services\GuestRequestRecoveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GuestRequestRecoveryLink extends Notification implements ShouldQueue
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
            ->subject('Retrouver votre demande — Tikmos')
            ->line('Connectez-vous ou créez un compte avec cette adresse email, puis vérifiez-la pour retrouver votre demande.')
            ->action('Retrouver ma demande', app(GuestRequestRecoveryService::class)->linkFor($this->request))
            ->line('Ce lien est personnel, valable 24 heures et utilisable une seule fois. Si vous n’avez rien demandé, ignorez cet email.');
    }
}
