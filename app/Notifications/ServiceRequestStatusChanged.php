<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ServiceRequest;
use App\Services\GuestRequestRecoveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Au client : le statut de sa demande a changé. */
class ServiceRequestStatusChanged extends Notification implements ShouldQueue
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
            ->subject('Mise à jour de votre demande — '.$this->request->reference)
            ->greeting('Bonjour '.$this->request->name.',')
            ->line('Le statut de votre demande a évolué : '.$this->request->status->label().'.')
            ->action('Suivre ma demande', app(GuestRequestRecoveryService::class)->linkFor($this->request));
    }
}
