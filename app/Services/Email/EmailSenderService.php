<?php

namespace App\Services\Email;

use App\Models\EmailSender;
use App\Models\EmailSenderAccount;
use Illuminate\Support\Facades\DB;
use App\Mail\TestEmail;

use App\Services\Email\Providers\ProviderDeliveryResult;

class EmailSenderService
{
    /**
     * Create Email Sender
     */
    public function create(array $data): EmailSender
    {
        return DB::transaction(function () use ($data) {

            /*
            |--------------------------------------------------------------------------
            | Create Sender
            |--------------------------------------------------------------------------
            */

            $sender = EmailSender::create([

                'organization_id' => auth()->user()?->organization_id ?? $data['organization_id'] ?? 1,

                'name' => $data['name'],

                'display_name' => $data['display_name'],

                'email' => $data['email'],

                'provider' => $data['provider'],

                'daily_limit' => $data['daily_limit'],

                'hourly_limit' => $data['hourly_limit'],

                'signature' => $data['signature'] ?? null,

                'created_by' => auth()->id(),

            ]);

            /*
            |--------------------------------------------------------------------------
            | Create Provider Account
            |--------------------------------------------------------------------------
            */

            EmailSenderAccount::create([

                'email_sender_id' => $sender->id,

                'settings' => $data['settings'],

            ]);

            return $sender->load('senderAccount');

        });
    }

    /**
     * Update Sender
     */
    public function update(
        EmailSender $sender,
        array $data
    ): EmailSender
    {
        return DB::transaction(function () use ($sender, $data) {

            $sender->update([

                'name' => $data['name'],

                'display_name' => $data['display_name'],

                'email' => $data['email'],

                'provider' => $data['provider'],

                'daily_limit' => $data['daily_limit'],

                'hourly_limit' => $data['hourly_limit'],

                'signature' => $data['signature'] ?? null,

            ]);

            $existingSettings = $sender->senderAccount?->settings ?? [];
            $newSettings = array_merge($existingSettings, $data['settings'] ?? []);

            $sender->senderAccount()->update([

                'settings' => $newSettings,

            ]);

            return $sender->fresh()->load('senderAccount');

        });
    }

    /**
     * Delete Sender
     */
    public function delete(
        EmailSender $sender
    ): void
    {
        $sender->delete();
    }

    /**
     * Test Sender Connection
     */
    public function test(
        EmailSender $sender
    ): bool
    {
        $provider = ProviderFactory::make($sender);

        return $provider->test(
            $sender->senderAccount->settings
        );
    }

    /**
     * Send Test Email
     */
    public function sendTestEmail(
        EmailSender $sender,
        array $payload
    ): ProviderDeliveryResult
    {
        $provider = ProviderFactory::make($sender);

        $mailable = (new TestEmail(
            $payload['message'] ?? 'Test Email Body',
            $payload['subject'] ?? null,
            $payload['html'] ?? null
        ))
            ->to($payload['to'])
            ->from(
                $sender->email,
                $sender->display_name
            );

        return $provider->send(
            $sender->senderAccount->settings,
            $mailable
        );
    }

    /**
     * Sync Inbox
     */
    public function sync(
        EmailSender $sender
    ): void
    {
        $provider = ProviderFactory::make($sender);

        $provider->sync();
    }
}