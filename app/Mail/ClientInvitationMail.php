<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $email,
        public readonly string $inviteUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Faça parte da WuWallet',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.client-invitation',
            with: [
                'email' => $this->email,
                'inviteUrl' => $this->inviteUrl,
            ],
        );
    }
}
