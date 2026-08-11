<?php

namespace App\Services\Email\Providers;

class ProviderDeliveryResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $providerMessageId = null,
        public readonly ?string $providerThreadId = null,
        public readonly mixed $providerResponse = null,
        public readonly ?string $errorMessage = null
    ) {}

    /**
     * Create a successful delivery result
     */
    public static function success(
        ?string $providerMessageId = null,
        ?string $providerThreadId = null,
        mixed $providerResponse = null
    ): self {
        return new self(
            success: true,
            providerMessageId: $providerMessageId,
            providerThreadId: $providerThreadId,
            providerResponse: $providerResponse
        );
    }

    /**
     * Create a failed delivery result
     */
    public static function failure(
        string $errorMessage,
        mixed $providerResponse = null
    ): self {
        return new self(
            success: false,
            errorMessage: $errorMessage,
            providerResponse: $providerResponse
        );
    }

    /**
     * Check if delivery was successful
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Check if delivery failed
     */
    public function isFailure(): bool
    {
        return !$this->success;
    }

    /**
     * Get error message
     */
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /**
     * Get provider message ID
     */
    public function getProviderMessageId(): ?string
    {
        return $this->providerMessageId;
    }

    /**
     * Convert result to array representation
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'provider_message_id' => $this->providerMessageId,
            'provider_thread_id' => $this->providerThreadId,
            'provider_response' => $this->providerResponse,
            'error_message' => $this->errorMessage,
        ];
    }
}
