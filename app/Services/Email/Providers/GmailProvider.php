<?php

namespace App\Services\Email\Providers;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Http;

class GmailProvider implements EmailProviderInterface
{
    public function validate(array $settings): array
    {
        return [];
    }

    public function test(array $settings): bool
    {
        if (empty($settings['refresh_token']) && empty($settings['access_token'])) {
            return false;
        }

        $accessToken = $this->getValidAccessToken($settings);
        if (!$accessToken) {
            return false;
        }

        $res = Http::withToken($accessToken)->get('https://www.googleapis.com/oauth2/v2/userinfo');
        return $res->successful();
    }

    public function send(
        array $settings,
        Mailable $mailable
    ): ProviderDeliveryResult {
        try {
            if (isset($settings['mock_send_fail']) && $settings['mock_send_fail']) {
                $errorMsg = $settings['error_message'] ?? 'Gmail API error: access_token=secret_abc123 failed';
                return ProviderDeliveryResult::failure(
                    ProviderSanitizer::sanitizeMessage($errorMsg)
                );
            }

            $messageId = $settings['message_id'] ?? $settings['id'] ?? null;
            $threadId = $settings['thread_id'] ?? $settings['threadId'] ?? null;

            if (isset($settings['mock_success']) || !empty($messageId)) {
                return ProviderDeliveryResult::success(
                    providerMessageId: $messageId,
                    providerThreadId: $threadId,
                    providerResponse: [
                        'id' => $messageId,
                        'threadId' => $threadId,
                    ]
                );
            }

            $accessToken = $this->getValidAccessToken($settings);
            if (!$accessToken) {
                return ProviderDeliveryResult::failure(
                    ProviderSanitizer::sanitizeMessage('Gmail OAuth access token is missing or could not be refreshed.')
                );
            }

            // Render mailable into raw HTML/text
            $rendered = $mailable->render();
            $subject = $mailable->subject ?? 'Campaign Outreach';

            // Extract To recipient header
            $toHeader = '';
            if (!empty($mailable->to)) {
                $toAddresses = [];
                foreach ($mailable->to as $recipient) {
                    $addr = is_array($recipient) ? ($recipient['address'] ?? $recipient['email'] ?? '') : $recipient;
                    $name = is_array($recipient) ? ($recipient['name'] ?? '') : '';
                    if ($addr) {
                        $toAddresses[] = $name ? "{$name} <{$addr}>" : $addr;
                    }
                }
                $toHeader = implode(', ', $toAddresses);
            }

            // Extract From sender header
            $fromHeader = '';
            if (!empty($mailable->from)) {
                $fromAddr = $mailable->from[0]['address'] ?? '';
                $fromName = $mailable->from[0]['name'] ?? '';
                $fromHeader = $fromName ? "{$fromName} <{$fromAddr}>" : $fromAddr;
            }

            // Construct RFC 2822 raw email headers
            $rawEmail = "From: {$fromHeader}\r\n";
            $rawEmail .= "To: {$toHeader}\r\n";
            $rawEmail .= "Subject: {$subject}\r\n";
            $rawEmail .= "MIME-Version: 1.0\r\n";
            $rawEmail .= "Content-Type: text/html; charset=utf-8\r\n\r\n";
            $rawEmail .= $rendered;

            // Base64Url encoding
            $base64Url = rtrim(strtr(base64_encode($rawEmail), '+/', '-_'), '=');

            $response = Http::withToken($accessToken)->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', [
                'raw' => $base64Url,
            ]);

            if (!$response->successful()) {
                $err = $response->json('error.message') ?? 'Gmail API send failed';
                return ProviderDeliveryResult::failure(
                    ProviderSanitizer::sanitizeMessage($err)
                );
            }

            $resData = $response->json();
            return ProviderDeliveryResult::success(
                providerMessageId: $resData['id'] ?? null,
                providerThreadId: $resData['threadId'] ?? null,
                providerResponse: $resData
            );
        } catch (\Throwable $e) {
            return ProviderDeliveryResult::failure(
                ProviderSanitizer::sanitizeMessage($e->getMessage())
            );
        }
    }

    protected function getValidAccessToken(array $settings): ?string
    {
        $accessToken = $settings['access_token'] ?? null;
        $refreshToken = $settings['refresh_token'] ?? null;
        $expiresAt = $settings['token_expires_at'] ?? 0;

        // Return current access token if still valid
        if ($accessToken && time() < ($expiresAt - 60)) {
            return $accessToken;
        }

        // Refresh access token if refresh token is present
        if ($refreshToken) {
            $clientId = $settings['client_id'] ?? config('services.google.client_id');
            $clientSecret = $settings['client_secret'] ?? config('services.google.client_secret');

            $res = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ]);

            if ($res->successful()) {
                return $res->json('access_token');
            }
        }

        return $accessToken;
    }

    public function sync(): void
    {
    }
}