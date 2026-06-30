<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorCodeNotification extends Notification
{
    public function __construct(public string $code, public int $minutes = 10) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre code de connexion SEZY')
            ->greeting('Bonjour,')
            ->line('Voici votre code de connexion SEZY :')
            ->line('**'.$this->code.'**')
            ->line("Ce code expire dans {$this->minutes} minutes.")
            ->line("Si vous n'êtes pas à l'origine de cette connexion, ignorez cet email et changez votre mot de passe.");
    }
}
