<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class SystemNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $data;

    /**
     * Create a new notification instance.
     * $data should have ['title', 'message', 'type', 'url']
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database', 'broadcast', WebPushChannel::class];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return $this->data;
    }
    
    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'data' => $this->data,
            'created_at' => now()->toIso8601String(),
            'id' => $this->id,
        ]);
    }

    /**
     * Get the web push representation of the notification.
     */
    public function toWebPush($notifiable, $notification)
    {
        $url = $this->data['url'] ?? '/admin/dashboard';
        return (new WebPushMessage)
            ->title($this->data['title'] ?? 'Notifikasi Sistem')
            ->icon('/icons/icon-192.png')
            ->body($this->data['message'] ?? '')
            ->action('Lihat Detail', 'view_action')
            ->data(['url' => $url]);
    }
    
    /**
     * The event name to listen for on the client side.
     */
    public function broadcastType()
    {
        return 'SystemNotification';
    }
}
