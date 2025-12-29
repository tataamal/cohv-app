<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;

class WeeklyLogHistoryMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $filePaths;

    /**
     * Create a new message instance.
     * @param string|array $filePaths
     * @param string $subjectDate
     * @param string $bodyDate
     */
    public function __construct(string|array $filePaths, public string $subjectDate, public string $bodyDate)
    {
        $this->filePaths = is_array($filePaths) ? $filePaths : [$filePaths];
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Weekly Report WI_' . $this->subjectDate,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.weekly_log_history',
            with: ['dateInfo' => $this->bodyDate],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        $attachments = [];
        foreach ($this->filePaths as $path) {
            $attachments[] = Attachment::fromPath($path)
                ->as(basename($path))
                ->withMime('application/pdf');
        }
        return $attachments;
    }
}
