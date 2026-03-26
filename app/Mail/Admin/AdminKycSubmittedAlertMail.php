<?php

namespace App\Mail\Admin;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminKycSubmittedAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $payload)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('New KYC Verification Request - AF Home')
            ->view('emails.admin.kyc-submitted-alert')
            ->with([
                'payload' => $this->payload,
            ]);
    }
}
