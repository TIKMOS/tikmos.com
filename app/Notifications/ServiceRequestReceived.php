<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ServiceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/** À l'équipe : une nouvelle demande de devis est arrivée. */
class ServiceRequestReceived extends Notification implements ShouldQueue
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
            ->subject('Nouvelle demande de devis — '.$this->request->reference)
            ->greeting('Nouvelle demande')
            ->line('Demandeur : '.$this->request->name)
            ->line('Service : '.($this->request->service_id ? $this->request->service->title : 'Demande sur mesure'))
            ->line('Message : '.Str::limit($this->request->message, 200))
            ->action('Ouvrir la demande', route('admin.service-requests.show', $this->request->reference));
    }
}
