<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $token, public string $roleLabel) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('password.reset', ['token' => $this->token, 'email' => $notifiable->email], false));

        return (new MailMessage)
            ->subject("Tikmos — invitation à rejoindre l'équipe")
            ->greeting('Bonjour,')
            ->line("Vous avez été invité à rejoindre l'espace de gestion Tikmos en tant que **{$this->roleLabel}**.")
            ->action('Définir mon mot de passe', $url)
            ->line('Pour des raisons de sécurité, ce lien expire après un délai limité. Demandez un renvoi si besoin.');
    }
}
