<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CampaignLead;
use App\Models\EmailCampaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmailTrackingController extends Controller
{
    /**
     * Handle Email Open Pixel Tracking Request
     */
    public function trackOpen(Request $request, CampaignLead $campaignLead)
    {
        try {
            if (!$campaignLead->opened_at) {
                $campaignLead->opened_at = now();
                if ($campaignLead->status === 'sent') {
                    $campaignLead->status = 'opened';
                }
                $campaignLead->save();

                // Recalculate campaign open count
                $campaign = $campaignLead->campaign;
                if ($campaign) {
                    $openCount = CampaignLead::where('email_campaign_id', $campaign->id)
                        ->whereNotNull('opened_at')
                        ->count();
                    $campaign->update(['opened_count' => $openCount]);
                }
            }
        } catch (\Throwable $e) {
            Log::error("Failed to track email open for Lead #{$campaignLead->id}: " . $e->getMessage());
        }

        // Return 1x1 Transparent GIF Binary
        $gifBinary = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($gifBinary, 200)
            ->header('Content-Type', 'image/gif')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Handle Email Link Click Tracking Request
     */
    public function trackClick(Request $request, CampaignLead $campaignLead)
    {
        $targetUrl = $request->query('url');

        try {
            $campaignLead->clicked_at = $campaignLead->clicked_at ?? now();
            $campaignLead->opened_at = $campaignLead->opened_at ?? now();
            $campaignLead->status = 'clicked';
            $campaignLead->save();

            // Recalculate campaign click & open counts
            $campaign = $campaignLead->campaign;
            if ($campaign) {
                $openCount = CampaignLead::where('email_campaign_id', $campaign->id)
                    ->whereNotNull('opened_at')
                    ->count();
                $clickCount = CampaignLead::where('email_campaign_id', $campaign->id)
                    ->whereNotNull('clicked_at')
                    ->count();
                $campaign->update([
                    'opened_count' => $openCount,
                    'clicked_count' => $clickCount,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error("Failed to track email click for Lead #{$campaignLead->id}: " . $e->getMessage());
        }

        if (empty($targetUrl) || !filter_var($targetUrl, FILTER_VALIDATE_URL)) {
            $targetUrl = env('FRONTEND_URL', 'https://crm.wpthrust.in');
        }

        return redirect()->away($targetUrl);
    }

    /**
     * Handle Unsubscribe Request
     */
    public function trackUnsubscribe(Request $request, string $token)
    {
        $lead = CampaignLead::where('unsubscribe_token', $token)->first();

        $emailAddress = 'your email';

        if ($lead) {
            $lead->update([
                'status' => 'unsubscribed',
                'unsubscribed_at' => now(),
            ]);

            $emailAddress = $lead->business?->email ?? 'your email';
            $orgId = $lead->campaign?->organization_id;

            if ($orgId && $emailAddress && $emailAddress !== 'your email') {
                \App\Models\UnsubscribedEmail::firstOrCreate(
                    [
                        'organization_id' => $orgId,
                        'campaign_id' => $lead->email_campaign_id,
                        'email' => $emailAddress,
                    ],
                    [
                        'campaign_lead_id' => $lead->id,
                        'unsubscribed_at' => now(),
                    ]
                );
            }
        }

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribed Successfully</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background-color: #f8fafc; color: #1e293b; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
        .card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 40px; max-width: 440px; width: 100%; text-align: center; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05); }
        .icon { width: 64px; height: 64px; background: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 28px; }
        h1 { font-size: 22px; font-weight: 700; margin: 0 0 10px; color: #0f172a; }
        p { font-size: 14px; color: #64748b; margin: 0 0 24px; line-height: 1.5; }
        .email-badge { background: #f1f5f9; color: #334155; font-weight: 600; padding: 4px 10px; border-radius: 6px; font-size: 13px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">✓</div>
        <h1>You have been unsubscribed</h1>
        <p>Your email address <span class="email-badge">{$emailAddress}</span> has been removed from our outreach sequence and will not receive any further emails.</p>
    </div>
</body>
</html>
HTML;

        return response($html, 200)->header('Content-Type', 'text/html');
    }
}
