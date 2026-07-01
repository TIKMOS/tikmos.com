<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** À l'équipe : une nouvelle commande boutique. */
class OrderReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nouvelle commande — '.$this->order->number)
            ->greeting('Nouvelle commande boutique')
            ->line('Client : '.$this->order->contact_name)
            ->line('Total : '.number_format($this->order->total, 0, ',', ' ').' FCFA')
            ->line('Paiement à la livraison.')
            ->action('Voir la commande', route('admin.boutique.orders'));
    }
}
