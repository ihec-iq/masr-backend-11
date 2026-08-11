<?php

namespace App\Mail;

use App\Models\BackupLog;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BackupNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public array $payload,
        public string $event,
        public array $attachmentData = []
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $isSuccess = $this->event === 'success';
        $appName = config('app.name', 'Laravel');

        return new Envelope(
            subject: sprintf('[%s] Backup %s', $appName, strtoupper($this->event)),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.backup-notification',
            with: [
                'payload' => $this->payload,
                'event' => $this->event,
                'isSuccess' => $this->event === 'success',
            ]
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

        foreach ($this->attachmentData as $att) {
            $attachments[] = Attachment::fromData(
                fn () => $att['data'],
                $att['name']
            )->withMime('application/zip');
        }

        return $attachments;
    }
}
