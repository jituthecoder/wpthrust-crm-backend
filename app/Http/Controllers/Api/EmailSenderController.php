<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailSender;
use App\Services\Email\EmailSenderService;
use Illuminate\Http\Request;

class EmailSenderController extends Controller
{
    protected EmailSenderService $service;

    public function __construct(
        EmailSenderService $service
    ) {
        $this->service = $service;
    }

    /**
     * List Email Senders
     */
    public function index(Request $request)
    {
        $query = EmailSender::with('senderAccount');

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = trim($request->search);

            $query->where(function ($q) use ($search) {

                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");

            });

        }

        /*
        |--------------------------------------------------------------------------
        | Provider Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('provider')) {

            $query->where(
                'provider',
                $request->provider
            );

        }

        $senders = $query
            ->latest()
            ->paginate(20);

        return response()->json([

            'success' => true,

            'data' => $senders,

        ]);
    }

    /**
     * Create Email Sender
     */
    public function store(Request $request)
    {
        $validated = $request->validate([

            'name' => 'required|string|max:255',

            'display_name' => 'required|string|max:255',

            'email' => 'required|email|unique:email_senders,email',

            'provider' => 'required|string',

            'daily_limit' => 'required|integer|min:1',

            'hourly_limit' => 'required|integer|min:1',

            'signature' => 'nullable|string',

            'settings' => 'required|array',

        ]);

        $sender = $this->service->create($validated);

        return response()->json([

            'success' => true,

            'message' => 'Email sender created successfully.',

            'data' => $sender,

        ], 201);
    }

    /**
     * Show Email Sender
     */
    public function show(
        EmailSender $emailSender
    ) {
        return response()->json([

            'success' => true,

            'data' => $emailSender->load(
                'senderAccount'
            ),

        ]);
    }

    /**
     * Update Email Sender
     */
    public function update(
        Request $request,
        EmailSender $emailSender
    ) {

        $validated = $request->validate([

            'name' => 'required|string|max:255',

            'email' => 'required|email|unique:email_senders,email,' . $emailSender->id,

            'provider' => 'required|string',

            'daily_limit' => 'required|integer|min:1',

            'hourly_limit' => 'required|integer|min:1',

            'signature' => 'nullable|string',

            'settings' => 'required|array',

        ]);

        $sender = $this->service->update(
            $emailSender,
            $validated
        );

        return response()->json([

            'success' => true,

            'message' => 'Email sender updated successfully.',

            'data' => $sender,

        ]);
    }

    /**
     * Delete Email Sender
     */
    public function destroy(
        EmailSender $emailSender
    ) {

        $this->service->delete(
            $emailSender
        );

        return response()->json([

            'success' => true,

            'message' => 'Email sender deleted successfully.',

        ]);
    }


    /**
     * Test Email Sender
     */
    public function test(EmailSender $emailSender)
    {
        $success = $this->service->test($emailSender);

        return response()->json([

            'success' => $success,

            'message' => $success
                ? 'Connection successful.'
                : 'Connection failed.',

        ]);
    }


    /**
     * Send Test Email
     */
    public function sendTest(
        Request $request,
        EmailSender $emailSender
    )
    {
        $validated = $request->validate([

            'to' => 'required|email',

            'subject' => 'nullable|string|max:255',

            'message' => 'nullable|string',

            'html' => 'nullable|string',

        ]);

        $result = $this->service->sendTestEmail(
            $emailSender,
            $validated
        );

        if (!$result->isSuccess()) {
            return response()->json([
                'success' => false,
                'message' => $result->getErrorMessage() ?? 'Failed to send test email.',
            ], 422);
        }

        return response()->json([

            'success' => true,

            'message' => 'Test email sent successfully.',

            'data' => [
                'provider_message_id' => $result->getProviderMessageId(),
            ]

        ]);
    }
}