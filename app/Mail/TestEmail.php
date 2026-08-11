<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestEmail extends Mailable
{
    use Queueable, SerializesModels;

    public string $messageBody;
    public ?string $customSubject;

    public function __construct(string $messageBody, ?string $customSubject = null)
    {
        $this->messageBody = $messageBody;
        $this->customSubject = $customSubject;
    }

    public function build()
    {
        return $this
            ->subject($this->customSubject ?? 'WPThrust CRM - Test Email')
            ->view('emails.test-email');
    }
}