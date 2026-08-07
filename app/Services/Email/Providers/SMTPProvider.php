<?php

namespace App\Services\Email\Providers;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Throwable;
use App\Mail\TestEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Mailable;


class SMTPProvider implements EmailProviderInterface
{
    /**
     * Validation Rules
     */
    public function validate(array $settings): array
    {
        return [

            'settings.host' => 'required|string',

            'settings.port' => 'required|integer',

            'settings.username' => 'required|string',

            'settings.password' => 'required|string',

            'settings.encryption' => 'required|in:ssl,tls',

        ];
    }

    public function test(array $settings): bool
    {
        try {

            $dsn = sprintf(
                'smtp://%s:%s@%s:%s?encryption=%s',
                urlencode($settings['username']),
                urlencode($settings['password']),
                $settings['host'],
                $settings['port'],
                $settings['encryption']
            );

            $transport = Transport::fromDsn($dsn);

            /*
            |--------------------------------------------------------------------------
            | Force SMTP Connection
            |--------------------------------------------------------------------------
            */

            $transport->start();

            return true;

        } catch (Throwable $e) {

            return false;

        }
    }

    public function send(
        array $settings,
        Mailable $mailable
    ): bool
    {
        try {

            $dsn = sprintf(
                'smtp://%s:%s@%s:%s?encryption=%s',
                urlencode($settings['username']),
                urlencode($settings['password']),
                $settings['host'],
                $settings['port'],
                $settings['encryption']
            );

            $transport = Transport::fromDsn($dsn);

            $mailer = Mail::mailer('smtp');

            $mailer->setSymfonyTransport($transport);

            $mailer->send($mailable);

            return true;

        } catch (Throwable $e) {

            throw $e;

        }
    }

    public function sync(): void
    {

    }
}