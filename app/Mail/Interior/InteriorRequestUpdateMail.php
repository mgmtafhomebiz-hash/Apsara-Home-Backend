<?php

namespace App\Mail\Interior;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InteriorRequestUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $payload)
    {
    }

    public function build(): self
    {
        return $this
            ->subject(($this->payload['headline'] ?? 'Interior Request Update') . ' - AF Home')
            ->view('emails.interior.updated')
            ->with([
                'payload' => $this->payload,
            ]);
    }
}
