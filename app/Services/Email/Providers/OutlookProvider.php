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
    ): bool
    {
        return false;
    }

    public function sync(): void
    {

    }
}