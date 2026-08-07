<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CampaignEmail extends Mailable
{
    use Queueable, SerializesModels;

    public string $subjectLine;

    public string $htmlContent;

    public ?string $plainText;

    public function __construct(
        string $subjectLine,
        string $html,
        ?string $plainText = null
    ) {
        $this->subjectLine = $subjectLine;
        $this->htmlContent = $html;
        $this->plainText = $plainText;
    }

    public function build()
    {
        $mail = $this->subject($this->subjectLine)
            ->html($this->htmlContent);

        if (!empty($this->plainText)) {

            $mail->text(
                'emails.campaign_plain',
                [
                    'plainText' => $this->plainText,
                ]
            );

        }

        return $mail;
    }
}