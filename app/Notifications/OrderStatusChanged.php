<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Au client : le statut de sa commande a changé. */
class OrderStatusChanged extends Notification implements ShouldQueue
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
            ->subject('Mise à jour de votre commande — '.$this->order->number)
            ->greeting('Bonjour '.$this->order->contact_name.',')
            ->line('Votre commande est désormais : '.$this->order->status->label().'.')
            ->action('Suivre ma commande', route('app.orders.show', $this->order->number));
    }
}
