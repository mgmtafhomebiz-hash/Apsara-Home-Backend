<?php

namespace App\Mail\Interior;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InteriorRequestSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $payload)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('Interior Booking Request Received - AF Home')
            ->view('emails.interior.submitted')
            ->with([
                'payload' => $this->payload,
            ]);
    }
}
