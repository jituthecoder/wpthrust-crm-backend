<?php

namespace App\Services\Email;

use App\Models\EmailSender;
use App\Services\Email\Providers\EmailProviderInterface;
use App\Services\Email\Providers\SMTPProvider;
use App\Services\Email\Providers\GmailProvider;
use App\Services\Email\Providers\OutlookProvider;
use InvalidArgumentException;

class ProviderFactory
{
    /**
     * Create Provider Instance
     */
    public static function make(
        EmailSender $sender
    ): EmailProviderInterface {

        return match ($sender->provider) {

            'smtp' => new SMTPProvider(),

            'gmail' => new GmailProvider(),

            'outlook' => new OutlookProvider(),

            default => throw new InvalidArgumentException(
                "Unsupported provider: {$sender->provider}"
            ),

        };

    }
}