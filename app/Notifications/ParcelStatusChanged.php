<?php

namespace App\Notifications;

use App\Enums\ParcelStatus;
use App\Models\Parcel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ParcelStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Parcel $parcel, public ParcelStatus $status) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("SEZY — votre colis {$this->parcel->reference}")
            ->greeting('Bonjour,')
            ->line("Votre colis **{$this->parcel->reference}** a un nouveau statut : **{$this->status->label()}**.")
            ->action('Suivre mon colis', url('/suivi/'.$this->parcel->reference))
            ->line('Merci de votre confiance.');
    }

    /** @return array<string, string> */
    public function toArray(object $notifiable): array
    {
        return [
            'parcel_reference' => $this->parcel->reference,
            'status' => $this->status->value,
            'message' => "Colis {$this->parcel->reference} : {$this->status->label()}.",
        ];
    }
}
