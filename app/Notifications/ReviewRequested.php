<?php

namespace App\Notifications;

use App\Models\Parcel;
use App\Models\ReviewRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Parcel $parcel, public ReviewRequest $request) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return $notifiable->email ? ['mail', 'database'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('SEZY — votre colis est arrivé, donnez votre avis')
            ->greeting('Bonjour,')
            ->line("Votre colis **{$this->parcel->reference}** a bien été livré. Comment s'est passée l'expérience ?")
            ->action('Donner mon avis', url('/app/colis/'.$this->parcel->reference))
            ->line('Votre retour nous aide à nous améliorer. Merci !');
    }

    /** @return array<string, string> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'review_requested',
            'parcel_reference' => $this->parcel->reference,
            'message' => "Donnez votre avis sur le colis {$this->parcel->reference}.",
        ];
    }
}
