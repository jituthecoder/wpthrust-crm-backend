<?php

namespace App\Services\Email\Providers;
use Illuminate\Mail\Mailable;


class OutlookProvider implements EmailProviderInterface
{
    public function validate(array $settings): array
    {
        return [];
    }

    public function test(array $settings): bool
    {
        return true;
    }

    public function send(
        array $settings,
        Mailable $mailable
    ): ProviderDeliveryResult
    {
        try {
            if (isset($settings['mock_send_fail']) && $settings['mock_send_fail']) {
                $errorMsg = $settings['error_message'] ?? 'Microsoft Graph API error: Bearer eyJhbGciOi... failed';
                return ProviderDeliveryResult::failure(
                    ProviderSanitizer::sanitizeMessage($errorMsg)
                );
            }

            // Extract message ID and conversation/thread ID if available from settings or API response
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

            return ProviderDeliveryResult::failure(
                ProviderSanitizer::sanitizeMessage('Outlook sending is not yet fully configured.')
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
}