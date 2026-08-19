<?php

namespace App\Services\Email;

use App\Models\Business;

class TemplateVariableService
{
    /**
     * Available Variables
     */
    public function variables(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Business
            |--------------------------------------------------------------------------
            */

            [
                'group' => 'Business',
                'key' => 'business_name',
                'variable' => '{{business_name}}',
                'label' => 'Business Name',
            ],

            [
                'group' => 'Business',
                'key' => 'website',
                'variable' => '{{website}}',
                'label' => 'Website',
            ],

            [
                'group' => 'Business',
                'key' => 'email',
                'variable' => '{{email}}',
                'label' => 'Email',
            ],

            [
                'group' => 'Business',
                'key' => 'phone',
                'variable' => '{{phone}}',
                'label' => 'Phone',
            ],

            [
                'group' => 'Business',
                'key' => 'address',
                'variable' => '{{address}}',
                'label' => 'Address',
            ],

            [
                'group' => 'Business',
                'key' => 'city',
                'variable' => '{{city}}',
                'label' => 'City',
            ],

            [
                'group' => 'Business',
                'key' => 'state',
                'variable' => '{{state}}',
                'label' => 'State',
            ],

            [
                'group' => 'Business',
                'key' => 'country',
                'variable' => '{{country}}',
                'label' => 'Country',
            ],

            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            [
                'group' => 'Performance',
                'key' => 'mobile_pagespeed',
                'variable' => '{{mobile_pagespeed}}',
                'label' => 'Mobile PageSpeed',
            ],

            [
                'group' => 'Performance',
                'key' => 'desktop_pagespeed',
                'variable' => '{{desktop_pagespeed}}',
                'label' => 'Desktop PageSpeed',
            ],

            [
                'group' => 'Performance',
                'key' => 'mobile_seo',
                'variable' => '{{mobile_seo}}',
                'label' => 'Mobile SEO',
            ],

            [
                'group' => 'Performance',
                'key' => 'desktop_seo',
                'variable' => '{{desktop_seo}}',
                'label' => 'Desktop SEO',
            ],

            [
                'group' => 'Performance',
                'key' => 'mobile_accessibility',
                'variable' => '{{mobile_accessibility}}',
                'label' => 'Mobile Accessibility',
            ],

            [
                'group' => 'Performance',
                'key' => 'desktop_accessibility',
                'variable' => '{{desktop_accessibility}}',
                'label' => 'Desktop Accessibility',
            ],

            [
                'group' => 'Performance',
                'key' => 'mobile_load_time',
                'variable' => '{{mobile_load_time}}',
                'label' => 'Mobile Load Time',
            ],

            [
                'group' => 'Performance',
                'key' => 'desktop_load_time',
                'variable' => '{{desktop_load_time}}',
                'label' => 'Desktop Load Time',
            ],

            [
                'group' => 'Performance',
                'key' => 'mobile_lcp',
                'variable' => '{{mobile_lcp}}',
                'label' => 'Mobile LCP',
            ],

            [
                'group' => 'Performance',
                'key' => 'mobile_fcp',
                'variable' => '{{mobile_fcp}}',
                'label' => 'Mobile FCP',
            ],

            [
                'group' => 'Performance',
                'key' => 'mobile_tbt',
                'variable' => '{{mobile_tbt}}',
                'label' => 'Mobile TBT',
            ],

            [
                'group' => 'Performance',
                'key' => 'mobile_cls',
                'variable' => '{{mobile_cls}}',
                'label' => 'Mobile CLS',
            ],

            [
                'group' => 'Performance',
                'key' => 'mobile_speed_index',
                'variable' => '{{mobile_speed_index}}',
                'label' => 'Mobile Speed Index',
            ],

            [
                'group' => 'Performance',
                'key' => 'mobile_screenshot',
                'variable' => '{{mobile_screenshot}}',
                'label' => 'Mobile Screenshot (HTML Image)',
            ],

            [
                'group' => 'Performance',
                'key' => 'mobile_screenshot_url',
                'variable' => '{{mobile_screenshot_url}}',
                'label' => 'Mobile Screenshot URL',
            ],

            [
                'group' => 'Performance',
                'key' => 'desktop_lcp',
                'variable' => '{{desktop_lcp}}',
                'label' => 'Desktop LCP',
            ],

            /*
            |--------------------------------------------------------------------------
            | Google
            |--------------------------------------------------------------------------
            */

            [
                'group' => 'Google',
                'key' => 'average_rating',
                'variable' => '{{average_rating}}',
                'label' => 'Average Rating',
            ],

            [
                'group' => 'Google',
                'key' => 'review_count',
                'variable' => '{{review_count}}',
                'label' => 'Review Count',
            ],

            /*
            |--------------------------------------------------------------------------
            | Social
            |--------------------------------------------------------------------------
            */

            [
                'group' => 'Social',
                'key' => 'facebook',
                'variable' => '{{facebook}}',
                'label' => 'Facebook',
            ],

            [
                'group' => 'Social',
                'key' => 'instagram',
                'variable' => '{{instagram}}',
                'label' => 'Instagram',
            ],

            [
                'group' => 'Social',
                'key' => 'linkedin',
                'variable' => '{{linkedin}}',
                'label' => 'LinkedIn',
            ],

            [
                'group' => 'Website',
                'key' => 'contact_form',
                'variable' => '{{contact_form}}',
                'label' => 'Contact Form',
            ],

            [
                'group' => 'Performance',
                'key' => 'psi_analysis_url',
                'variable' => '{{psi_analysis_url}}',
                'label' => 'Google PageSpeed Direct Live Analysis URL',
            ],

            [
                'group' => 'Performance',
                'key' => 'pagespeed_analysis_url',
                'variable' => '{{pagespeed_analysis_url}}',
                'label' => 'Google PageSpeed Direct Live Analysis URL (Alias)',
            ],

        ];
    }

    /**
     * Replace Variables (supports Business or ContactListLead)
     */
    public function render(
        string $content,
        $business
    ): string
    {
        $audit = null;
        if ($business instanceof \App\Models\Business) {
            $business->loadMissing('audit');
            $audit = $business->audit;
        } elseif (!empty($business->business_id)) {
            $realBiz = \App\Models\Business::find($business->business_id);
            if ($realBiz) {
                $realBiz->loadMissing('audit');
                $audit = $realBiz->audit;
            }
        }
        $screenshotUrl = optional($audit)->mobile_screenshot_url;

        // Clean website URL: strip query parameters (?utm_source=...), fragments, and leading protocol
        $cleanWebsite = $this->cleanWebsiteUrl($business->website);
        $fullCleanWebsite = !empty($cleanWebsite) ? 'https://' . $cleanWebsite . '/' : '';
        $psiAnalysisUrl = !empty($fullCleanWebsite) 
            ? 'https://pagespeed.web.dev/analysis?url=' . urlencode($fullCleanWebsite)
            : 'https://pagespeed.web.dev/';

        $replace = [

            '{{business_name}}' => $business->business_name ?? '',

            '{{website}}' => $cleanWebsite,

            '{{psi_analysis_url}}' => $psiAnalysisUrl,

            '{{pagespeed_analysis_url}}' => $psiAnalysisUrl,

            '{{email}}' => $business->email ?? '',

            '{{phone}}' => $business->phone ?? '',

            '{{category}}' => $business->category ?? '',

            '{{address}}' => $business->address ?? '',

            '{{city}}' => $business->city ?? '',

            '{{state}}' => $business->state ?? '',

            '{{zip_code}}' => $business->zip_code ?? '',

            '{{country}}' => $business->country ?? '',

            '{{today}}' => now()->format('d M Y'),

            '{{current_year}}' => now()->year,

            '{{average_rating}}' => optional($audit)->average_rating ?? '',

            '{{review_count}}' => optional($audit)->review_count ?? '',

            '{{mobile_pagespeed}}' => $business->mobile_pagespeed ?? optional($audit)->mobile_pagespeed ?? '',

            '{{desktop_pagespeed}}' => optional($audit)->desktop_pagespeed ?? '',

            '{{mobile_seo}}' => optional($audit)->mobile_seo ?? '',

            '{{desktop_seo}}' => optional($audit)->desktop_seo ?? '',

            '{{mobile_accessibility}}' => optional($audit)->mobile_accessibility ?? '',

            '{{desktop_accessibility}}' => optional($audit)->desktop_accessibility ?? '',

            '{{mobile_load_time}}' => optional($audit)->mobile_load_time ?? '',

            '{{desktop_load_time}}' => optional($audit)->desktop_load_time ?? '',

            '{{mobile_lcp}}' => optional($audit)->mobile_lcp ?? '',

            '{{mobile_fcp}}' => optional($audit)->mobile_fcp ?? '',

            '{{mobile_tbt}}' => optional($audit)->mobile_tbt ?? '',

            '{{mobile_cls}}' => optional($audit)->mobile_cls ?? '',

            '{{mobile_speed_index}}' => optional($audit)->mobile_speed_index ?? '',

            '{{mobile_screenshot_url}}' => $screenshotUrl ?? '',

            '{{mobile_screenshot}}' => $screenshotUrl 
                ? '<img src="' . htmlspecialchars($screenshotUrl) . '" alt="Website Mobile Screenshot" style="max-width: 100%; max-height: 220px; width: auto; height: auto; border-radius: 6px; border: 1px solid #e2e8f0; object-fit: contain; display: block; margin: 0 auto;" />'
                : '',

            '{{desktop_lcp}}' => optional($audit)->desktop_lcp ?? '',

            '{{facebook}}' => optional($audit)->facebook ?? '',

            '{{instagram}}' => optional($audit)->instagram ?? '',

            '{{linkedin}}' => optional($audit)->linkedin ?? '',

            '{{contact_form}}' => optional($audit)->contact_form
                ? 'Available'
                : 'Not Available',

        ];

        return str_replace(
            array_keys($replace),
            array_values($replace),
            $content
        );
    }

    /**
     * Clean raw website URL: remove UTM tracking parameters, fragments, and leading protocol.
     */
    public function cleanWebsiteUrl(?string $url): string
    {
        if (empty($url)) {
            return '';
        }

        $url = trim($url);

        // Remove query parameters (?utm_source=... etc)
        if (str_contains($url, '?')) {
            $url = explode('?', $url)[0];
        }

        // Remove fragment (#...)
        if (str_contains($url, '#')) {
            $url = explode('#', $url)[0];
        }

        // Remove leading protocol
        $url = preg_replace('~^https?://~i', '', $url);

        return rtrim($url, '/');
    }
}