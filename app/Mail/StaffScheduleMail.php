<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Contracts\Queue\ShouldQueue;

class StaffScheduleMail extends Mailable
{
    use Queueable, SerializesModels;

    public $emailMessage;
    public $attachment;

    public $staff;
    public $date;
    public $time;

    public function __construct($staff, $date, $time)
    {
        $this->staff = $staff;
        $this->date = $date;
        $this->time = $time;
    }

    public function build()
    {
        return $this->subject('Staff Schedule')->markdown('email.staff_schedule');
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
                Attachment::fromPath(storage_path($this->attachment))->as('promo-photo.jpg')->withMime('image/jpeg'),    
            ];
        }else{
            return []; 
        }
    }
}
