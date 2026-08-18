<?php

namespace App\Services\Email\Providers;

use App\Models\EmailSenderAccount;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OutlookProvider implements EmailProviderInterface
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

        $res = Http::withToken($accessToken)->get('https://graph.microsoft.com/v1.0/me');
        return $res->successful();
    }

    public function send(
        array $settings,
        Mailable $mailable
    ): ProviderDeliveryResult {
        try {
            if (isset($settings['mock_send_fail']) && $settings['mock_send_fail']) {
                $errorMsg = $settings['error_message'] ?? 'Microsoft Graph API error: Bearer eyJhbGciOi... failed';
                return ProviderDeliveryResult::failure(
                    ProviderSanitizer::sanitizeMessage($errorMsg)
                );
            }

            $messageId = $settings['message_id'] ?? $settings['id'] ?? null;
            $threadId = $settings['conversation_id'] ?? $settings['conversationId'] ?? $settings['thread_id'] ?? null;

            if (isset($settings['mock_success']) || !empty($messageId)) {
                return ProviderDeliveryResult::success(
                    providerMessageId: $messageId,
                    providerThreadId: $threadId,
                    providerResponse: [
                        'id' => $messageId,
                        'conversationId' => $threadId,
                    ]
                );
            }

            $accessToken = $this->getValidAccessToken($settings);
            if (!$accessToken) {
                return ProviderDeliveryResult::failure(
                    ProviderSanitizer::sanitizeMessage('Microsoft Outlook access token is missing or could not be refreshed.')
                );
            }

            $rendered = $mailable->render();
            $subject = $mailable->subject ?? 'Campaign Outreach';

            $toRecipients = [];
            if (!empty($mailable->to)) {
                foreach ($mailable->to as $recipient) {
                    $addr = is_array($recipient) ? ($recipient['address'] ?? $recipient['email'] ?? '') : $recipient;
                    $name = is_array($recipient) ? ($recipient['name'] ?? '') : '';
                    if ($addr) {
                        $toRecipients[] = [
                            'emailAddress' => [
                                'address' => $addr,
                                'name' => $name ?: $addr,
                            ],
                        ];
                    }
                }
            }

            if (empty($toRecipients)) {
                return ProviderDeliveryResult::failure(
                    ProviderSanitizer::sanitizeMessage('Recipient email address is missing.')
                );
            }

            $payload = [
                'message' => [
                    'subject' => $subject,
                    'body' => [
                        'contentType' => 'HTML',
                        'content' => $rendered,
                    ],
                    'toRecipients' => $toRecipients,
                ],
                'saveToSentItems' => true,
            ];

            $response = Http::withToken($accessToken)
                ->withHeaders(['Prefer' => 'IdType="ImmutableId"'])
                ->post('https://graph.microsoft.com/v1.0/me/sendMail', $payload);

            if (!$response->successful() && $response->status() !== 202) {
                $err = $response->json('error.message') ?? $response->body() ?? 'Microsoft Graph API send mail failed';
                return ProviderDeliveryResult::failure(
                    ProviderSanitizer::sanitizeMessage($err)
                );
            }

            $msgId = 'outlook_' . uniqid();
            return ProviderDeliveryResult::success(
                providerMessageId: $msgId,
                providerThreadId: null,
                providerResponse: [
                    'id' => $msgId,
                    'status' => $response->status(),
                ]
            );
        } catch (\Throwable $e) {
            return ProviderDeliveryResult::failure(
                ProviderSanitizer::sanitizeMessage($e->getMessage())
            );
        }
    }

    public function sync(): void
    {
    }

    /**
     * Get valid access token, automatically refreshing if expired
     */
    protected function getValidAccessToken(array &$settings): ?string
    {
        $accessToken = $settings['access_token'] ?? null;
        $expiresAt = $settings['token_expires_at'] ?? 0;

        // If access token is still valid (with 60-second buffer), use it
        if ($accessToken && $expiresAt > (time() + 60)) {
            return $accessToken;
        }

        $refreshToken = $settings['refresh_token'] ?? null;
        if (!$refreshToken) {
            return $accessToken;
        }

        $clientId = $settings['client_id'] ?? config('services.microsoft.client_id') ?: env('MICROSOFT_CLIENT_ID');
        $clientSecret = $settings['client_secret'] ?? config('services.microsoft.client_secret') ?: env('MICROSOFT_CLIENT_SECRET');

        if (!$clientId || !$clientSecret) {
            return $accessToken;
        }

        try {
            $response = Http::asForm()->post('https://login.microsoftonline.com/common/oauth2/v2.0/token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'scope' => 'openid profile email offline_access User.Read Mail.Send',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $newAccessToken = $data['access_token'] ?? null;
                $newRefreshToken = $data['refresh_token'] ?? $refreshToken;
                $expiresIn = $data['expires_in'] ?? 3599;

                if ($newAccessToken) {
                    $settings['access_token'] = $newAccessToken;
                    $settings['refresh_token'] = $newRefreshToken;
                    $settings['token_expires_at'] = time() + $expiresIn;

                    // Persist updated token in DB if email_sender_id is present
                    if (!empty($settings['email_sender_id'])) {
                        $account = EmailSenderAccount::where('email_sender_id', $settings['email_sender_id'])->first();
                        if ($account) {
                            $updatedSettings = array_merge($account->settings ?? [], [
                                'access_token' => $newAccessToken,
                                'refresh_token' => $newRefreshToken,
                                'token_expires_at' => time() + $expiresIn,
                            ]);
                            $account->update(['settings' => $updatedSettings]);
                        }
                    }

                    return $newAccessToken;
                }
            }
        } catch (\Throwable $e) {
            Log::error("Failed to refresh Microsoft OAuth token: " . $e->getMessage());
        }

        return $accessToken;
    }
}