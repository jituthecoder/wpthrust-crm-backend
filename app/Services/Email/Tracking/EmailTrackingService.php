<?php

namespace App\Services\Email\Tracking;

use App\Models\CampaignLead;

class EmailTrackingService
{
    /**
     * Prepare tracked HTML email body with open pixel and click tracking links.
     *
     * @param string $html
     * @param CampaignLead $lead
     * @return string
     */
    public function prepareTrackedHtml(string $html, CampaignLead $lead): string
    {
        if (empty($html)) {
            return $html;
        }

        $htmlWithClickTracking = $this->rewriteLinks($html, $lead);
        return $this->injectOpenPixel($htmlWithClickTracking, $lead);
    }

    /**
     * Inject 1x1 transparent open tracking pixel image before </body> or at the end of HTML.
     *
     * @param string $html
     * @param CampaignLead $lead
     * @return string
     */
    public function injectOpenPixel(string $html, CampaignLead $lead): string
    {
        $baseUrl = rtrim(config('app.url'), '/');
        $openUrl = "{$baseUrl}/api/track/open/{$lead->id}";

        $pixelTag = sprintf(
            '<img src="%s" alt="" width="1" height="1" style="display:none !important; min-height:1px !important; min-width:1px !important; border:0 !important; margin:0 !important; padding:0 !important;" />',
            htmlspecialchars($openUrl, ENT_QUOTES, 'UTF-8')
        );

        if (stripos($html, '</body>') !== false) {
            return str_ireplace('</body>', $pixelTag . '</body>', $html);
        }

        return $html . $pixelTag;
    }

    /**
     * Rewrite <a href="..."> links to route through click tracking endpoint.
     *
     * @param string $html
     * @param CampaignLead $lead
     * @return string
     */
    public function rewriteLinks(string $html, CampaignLead $lead): string
    {
        $baseUrl = rtrim(config('app.url'), '/');
        $clickEndpoint = "{$baseUrl}/api/track/click/{$lead->id}";

        $pattern = '/<a\s+([^>]*?)href=["\']([^"\']+)["\']([^>]*?)>/i';

        return preg_replace_callback($pattern, function ($matches) use ($clickEndpoint) {
            $beforeHref = $matches[1];
            $originalUrl = $matches[2];
            $afterHref = $matches[3];

            // Ignore mailto, tel, javascript, anchors, or unsubscribe links
            if (
                preg_match('/^(mailto:|tel:|javascript:|#|data:)/i', $originalUrl) ||
                str_contains(strtolower($originalUrl), 'unsubscribe')
            ) {
                return $matches[0];
            }

            $trackedUrl = $clickEndpoint . '?url=' . urlencode($originalUrl);

            return sprintf('<a %shref="%s"%s>', $beforeHref, htmlspecialchars($trackedUrl, ENT_QUOTES, 'UTF-8'), $afterHref);
        }, $html);
    }
}
