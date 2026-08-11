<?php

namespace App\Services\Email\Providers;
use Illuminate\Mail\Mailable;

interface EmailProviderInterface
{
    /**
     * Validate Provider Settings
     */
    public function validate(array $settings): array;

    /**
     * Test Connection
     */
    public function test(array $settings): bool;

    /**
     * Send Email
     */
    public function send(
        array $settings,
        Mailable $mailable
    ): ProviderDeliveryResult;

    /**
     * Sync Inbox
     */
    public function sync(): void;

    
}