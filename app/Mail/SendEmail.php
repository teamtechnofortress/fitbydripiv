<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;

class SendEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $emailMessage;
    public $attachment;
    /**
     * Create a new message instance.
     */
    public function __construct($emailMessage, $attachments)
    {
        //
        $this->emailMessage = $emailMessage;
        $this->attachment = $attachments;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Promotion From FitByDrip',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'email.emailCampaign',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        if($this->attachment){
            return [            
                Attachment::fromPath(storage_path($this->attachment))
                    ->as('promo-photo.jpg')
                    ->withMime('image/jpeg'),
    
                // Add more if needed:
                // Attachment::fromPath(storage_path('app/public/files/promo.pdf'))
                //     ->as('promo.pdf')
                //     ->withMime('application/pdf'),
            ];
        }else{
            return [];
        }
    }
}
