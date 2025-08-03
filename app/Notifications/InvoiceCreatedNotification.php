<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class InvoiceCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $invoice;

    /**
     * Create a new notification instance.
     */
    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        Carbon::setLocale('id');
        $siswaName = $this->invoice->siswa->nama_siswa;
        $period = $this->invoice->periode_tagihan->isoFormat('MMMM YYYY');
        $portalUrl = route('login'); 

        return (new MailMessage)
                    ->subject("Tagihan SPP Baru Telah Terbit - {$period}")
                    ->greeting("Halo, {$notifiable->name}!")
                    ->line("Tagihan SPP untuk siswa atas nama **{$siswaName}** periode **{$period}** telah terbit.")
                    ->line('Silakan masuk ke portal siswa Anda untuk melihat detail tagihan dan melakukan pembayaran.')
                    ->action('Masuk ke Portal Siswa', $portalUrl)
                    ->line('Terima kasih atas perhatian Anda.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
