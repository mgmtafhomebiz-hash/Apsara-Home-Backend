<?php

namespace App\Mail\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerPasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $email,
        public string $resetUrl,
        public string $expiresAt,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('Reset your AF Home password')
            ->view('emails.auth.customer-password-reset');
    }
}
