<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class GuestRequestEmailVerification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $token) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute('request-recovery.verify', now()->addHour(), [
            'id' => $notifiable->id,
            'hash' => sha1($notifiable->getEmailForVerification()),
            'token' => $this->token,
        ]);

        return (new MailMessage)
            ->subject('Vérifiez votre adresse email — Tikmos')
            ->line('Vérifiez l’adresse de votre compte pour retrouver votre demande. Le lien est valable une heure.')
            ->action('Vérifier mon adresse email', $url);
    }
}
