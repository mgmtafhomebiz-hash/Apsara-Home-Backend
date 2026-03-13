<?php

namespace App\Mail\Supplier;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SupplierInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $email,
        public string $supplierName,
        public string $setupUrl,
        public string $expiresAt,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('You have been invited to AF Home Supplier Portal')
            ->view('emails.supplier.supplier-invite');
    }
}
