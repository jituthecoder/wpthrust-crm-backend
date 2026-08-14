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
        $htmlWithUnsubscribe = $this->injectUnsubscribeFooter($htmlWithClickTracking, $lead);
        return $this->injectOpenPixel($htmlWithUnsubscribe, $lead);
    }

    /**
     * Inject Unsubscribe footer link or replace {{unsubscribe_url}} placeholder.
     */
    public function injectUnsubscribeFooter(string $html, CampaignLead $lead): string
    {
        if (empty($lead->unsubscribe_token)) {
            $lead->unsubscribe_token = \Illuminate\Support\Str::random(32);
            $lead->saveQuietly();
        }

        $baseUrl = rtrim(config('app.url'), '/');
        $unsubscribeUrl = "{$baseUrl}/api/track/unsubscribe/{$lead->unsubscribe_token}";

        if (str_contains($html, '{{unsubscribe_url}}')) {
            return str_replace('{{unsubscribe_url}}', htmlspecialchars($unsubscribeUrl, ENT_QUOTES, 'UTF-8'), $html);
        }

        $footerHtml = sprintf(
            '<div style="margin-top: 32px; border-top: 1px solid #e2e8f0; padding-top: 16px; text-align: center; font-family: sans-serif; font-size: 11px; color: #94a3b8;"><p style="margin: 0;">If you no longer wish to receive these emails, you can <a href="%s" style="color: #64748b; text-decoration: underline;">unsubscribe here</a>.</p></div>',
            htmlspecialchars($unsubscribeUrl, ENT_QUOTES, 'UTF-8')
        );

        if (stripos($html, '</body>') !== false) {
            return str_ireplace('</body>', $footerHtml . '</body>', $html);
        }

        return $html . $footerHtml;
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
