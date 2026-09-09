<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

class RegistrationSuccess extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Data pendaftaran yang akan ditampilkan di email.
     * @var array
     */
    public $registrationData;

    /**
     * Data invoice pendaftaran.
     * @var Invoice|null
     */
    public $invoice;

    /**
     * Create a new message instance.
     *
     * @param array $registrationData
     * @param Invoice|null $invoice
     * @return void
     */
    public function __construct(array $registrationData, Invoice $invoice = null)
    {
        $this->registrationData = $registrationData;
        $this->invoice = $invoice;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pendaftaran Berhasil!',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.registration-success', 
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];
        
        if ($this->invoice) {
            $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $this->invoice]);
            $attachments[] = Attachment::fromData(fn () => $pdf->output(), 'Invoice_Pendaftaran.pdf')
                                ->withMime('application/pdf');
        }

        return $attachments;
    }
}

