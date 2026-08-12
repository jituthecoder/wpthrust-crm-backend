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
}
