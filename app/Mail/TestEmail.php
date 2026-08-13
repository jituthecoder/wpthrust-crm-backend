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
    public ?string $htmlBody;

    public function __construct(string $messageBody, ?string $customSubject = null, ?string $htmlBody = null)
    {
        $this->messageBody = $messageBody;
        $this->customSubject = $customSubject;
        $this->htmlBody = $htmlBody;
    }

    public function build()
    {
        $mail = $this->subject($this->customSubject ?? 'WPThrust CRM - Test Email');

        if (!empty($this->htmlBody)) {
            return $mail->html($this->htmlBody);
        }

        return $mail->view('emails.test-email');
    }
}