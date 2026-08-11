<?php

namespace App\Services\Email\Providers;

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Throwable;
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
            | Force SMTP Connection Test
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
    ): ProviderDeliveryResult {
        try {
            $dsn = sprintf(
                'smtp://%s:%s@%s:%s?encryption=%s',
                urlencode($settings['username'] ?? ''),
                urlencode($settings['password'] ?? ''),
                $settings['host'] ?? '',
                $settings['port'] ?? '',
                $settings['encryption'] ?? 'tls'
            );

            $transport = Transport::fromDsn($dsn);
            $symfonyMailer = new Mailer($transport);

            // Render HTML content
            $html = $mailable->render();

            $email = (new Email())
                ->subject($mailable->subject ?? 'WPThrust Lead CRM')
                ->html($html);

            // Set From address
            $fromAddress = $mailable->from[0]['address'] ?? $settings['username'] ?? null;
            $fromName = $mailable->from[0]['name'] ?? '';
            if ($fromAddress) {
                $email->from(new Address($fromAddress, $fromName));
            }

            // Set To recipient addresses
            if (!empty($mailable->to)) {
                foreach ($mailable->to as $recipient) {
                    $addr = is_array($recipient) ? ($recipient['address'] ?? $recipient['email'] ?? null) : $recipient;
                    $name = is_array($recipient) ? ($recipient['name'] ?? '') : '';
                    if ($addr) {
                        $email->addTo(new Address($addr, $name));
                    }
                }
            }

            $symfonyMailer->send($email);
            $messageId = $email->generateMessageId();

            return ProviderDeliveryResult::success(
                providerMessageId: $messageId,
                providerResponse: ['message_id' => $messageId]
            );
        } catch (Throwable $e) {
            $sanitizedError = ProviderSanitizer::sanitizeMessage($e->getMessage());

            return ProviderDeliveryResult::failure($sanitizedError);
        }
    }

    public function sync(): void
    {
    }
}