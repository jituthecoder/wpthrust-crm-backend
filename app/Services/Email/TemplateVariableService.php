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

        ];
    }

    /**
     * Replace Variables
     */
    public function render(
        string $content,
        Business $business
    ): string
    {
        $audit = $business->audit;

        $replace = [

            '{{business_name}}' => $business->business_name,

            '{{website}}' => $business->website,

            '{{email}}' => $business->email,

            '{{phone}}' => $business->phone,

            '{{address}}' => $business->address,

            '{{city}}' => $business->city,

            '{{state}}' => $business->state,

            '{{country}}' => $business->country,

            '{{average_rating}}' => optional($audit)->average_rating,

            '{{review_count}}' => optional($audit)->review_count,

            '{{mobile_pagespeed}}' => optional($audit)->mobile_pagespeed,

            '{{desktop_pagespeed}}' => optional($audit)->desktop_pagespeed,

            '{{mobile_seo}}' => optional($audit)->mobile_seo,

            '{{desktop_seo}}' => optional($audit)->desktop_seo,

            '{{mobile_accessibility}}' => optional($audit)->mobile_accessibility,

            '{{desktop_accessibility}}' => optional($audit)->desktop_accessibility,

            '{{mobile_load_time}}' => optional($audit)->mobile_load_time,

            '{{desktop_load_time}}' => optional($audit)->desktop_load_time,

            '{{mobile_lcp}}' => optional($audit)->mobile_lcp,

            '{{desktop_lcp}}' => optional($audit)->desktop_lcp,

            '{{facebook}}' => optional($audit)->facebook,

            '{{instagram}}' => optional($audit)->instagram,

            '{{linkedin}}' => optional($audit)->linkedin,

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
}