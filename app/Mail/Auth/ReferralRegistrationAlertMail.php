<?php

namespace App\Mail\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReferralRegistrationAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $payload)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('New Referral Registration on AF Home')
            ->view('emails.auth.referral-registration-alert')
            ->with([
                'payload' => $this->payload,
            ]);
    }
}
