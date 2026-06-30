<?php

namespace App\Notifications;

use App\Models\Parcel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ParcelReady extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Parcel $parcel) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("SEZY — votre colis {$this->parcel->reference} est disponible")
            ->greeting('Bonne nouvelle !')
            ->line("Votre colis **{$this->parcel->reference}** est arrivé et disponible à la récupération ({$this->parcel->destination->label()}).")
            ->action('Voir les détails', url('/suivi/'.$this->parcel->reference))
            ->line('Présentez votre référence pour le retrait.');
    }

    /** @return array<string, string> */
    public function toArray(object $notifiable): array
    {
        return [
            'parcel_reference' => $this->parcel->reference,
            'status' => 'ready_for_pickup',
            'message' => "Votre colis {$this->parcel->reference} est disponible.",
        ];
    }
}
