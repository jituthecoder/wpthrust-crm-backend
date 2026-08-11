<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailSender;
use App\Models\EmailSenderAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OAuthController extends Controller
{
    /**
     * Redirect to Google OAuth Consent Screen
     */
    public function googleRedirect(Request $request)
    {
        $clientId = config('services.google.client_id') ?: env('GOOGLE_CLIENT_ID');
        $redirectUri = config('services.google.redirect_uri') ?: env('GOOGLE_REDIRECT_URI', 'https://api-crm.wpthrust.in/api/oauth/google/callback');

        if (empty($clientId)) {
            return response()->json([
                'success' => false,
                'message' => 'GOOGLE_CLIENT_ID is not configured in .env file.',
            ], 422);
        }

        $userId = Auth::id() ?? $request->query('user_id', 1);
        $orgId = Auth::user()?->organization_id ?? $request->query('organization_id', 1);

        $stateData = base64_encode(json_encode([
            'user_id' => $userId,
            'organization_id' => $orgId,
            'token' => Str::random(16),
        ]));

        $scopes = [
            'https://www.googleapis.com/auth/gmail.send',
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/userinfo.profile',
        ];

        $params = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $stateData,
        ]);

        $url = 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;

        if ($request->wantsJson() || $request->query('mode') === 'json') {
            return response()->json([
                'success' => true,
                'url' => $url,
            ]);
        }

        return redirect()->away($url);
    }

    /**
     * Handle Google OAuth Callback
     */
    public function googleCallback(Request $request)
    {
        $frontendUrl = rtrim(env('FRONTEND_URL', 'https://crm.wpthrust.in'), '/');
        $redirectTabUrl = $frontendUrl . '/email-campaigns?tab=senders';

        try {
            $code = $request->query('code');
            $error = $request->query('error');

            if ($error || empty($code)) {
                return redirect($redirectTabUrl . '&oauth_error=' . urlencode($error ?? 'Authorization code missing'));
            }

            $clientId = config('services.google.client_id') ?: env('GOOGLE_CLIENT_ID');
            $clientSecret = config('services.google.client_secret') ?: env('GOOGLE_CLIENT_SECRET');
            $redirectUri = config('services.google.redirect_uri') ?: env('GOOGLE_REDIRECT_URI', 'https://api-crm.wpthrust.in/api/oauth/google/callback');

            if (empty($clientId) || empty($clientSecret)) {
                return redirect($redirectTabUrl . '&oauth_error=' . urlencode('GOOGLE_CLIENT_ID or GOOGLE_CLIENT_SECRET is missing in server .env'));
            }

            // Exchange authorization code for access & refresh tokens
            $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
                'code' => $code,
            ]);

            if (!$tokenResponse->successful()) {
                $err = $tokenResponse->json('error_description') ?? $tokenResponse->json('error') ?? 'Failed to exchange authorization code with Google';
                return redirect($redirectTabUrl . '&oauth_error=' . urlencode($err));
            }

            $tokenData = $tokenResponse->json();
            $accessToken = $tokenData['access_token'] ?? null;
            $refreshToken = $tokenData['refresh_token'] ?? null;
            $expiresIn = $tokenData['expires_in'] ?? 3599;

            // Fetch Google User Profile
            $userInfoRes = Http::withToken($accessToken)->get('https://www.googleapis.com/oauth2/v2/userinfo');
            if (!$userInfoRes->successful()) {
                return redirect($redirectTabUrl . '&oauth_error=Failed+to+fetch+Google+user+profile');
            }

            $userInfo = $userInfoRes->json();
            $email = $userInfo['email'] ?? null;
            $name = $userInfo['name'] ?? $userInfo['given_name'] ?? 'Google Sender';

            if (empty($email)) {
                return redirect($redirectTabUrl . '&oauth_error=Google+profile+did+not+provide+email');
            }

            // Decode state if present
            $stateRaw = $request->query('state');
            $stateData = json_decode(base64_decode($stateRaw), true) ?? [];
            $userId = $stateData['user_id'] ?? Auth::id() ?? 1;
            $orgId = $stateData['organization_id'] ?? Auth::user()?->organization_id ?? 1;

            DB::transaction(function () use ($orgId, $userId, $email, $name, $clientId, $clientSecret, $accessToken, $refreshToken, $expiresIn) {
                $sender = EmailSender::updateOrCreate(
                    [
                        'organization_id' => $orgId,
                        'email' => $email,
                        'provider' => 'gmail',
                    ],
                    [
                        'name' => $name . ' (Gmail)',
                        'display_name' => $name,
                        'daily_limit' => 500,
                        'hourly_limit' => 50,
                        'is_active' => true,
                        'created_by' => $userId,
                    ]
                );

                EmailSenderAccount::updateOrCreate(
                    [
                        'email_sender_id' => $sender->id,
                    ],
                    [
                        'settings' => [
                            'client_id' => $clientId,
                            'client_secret' => $clientSecret,
                            'access_token' => $accessToken,
                            'refresh_token' => $refreshToken,
                            'token_expires_at' => now()->addSeconds($expiresIn)->timestamp,
                        ],
                    ]
                );
            });

            return redirect($redirectTabUrl . '&oauth_success=' . urlencode("Google account ({$email}) connected successfully!"));
        } catch (\Throwable $e) {
            return redirect($redirectTabUrl . '&oauth_error=' . urlencode($e->getMessage()));
        }
    }
}
