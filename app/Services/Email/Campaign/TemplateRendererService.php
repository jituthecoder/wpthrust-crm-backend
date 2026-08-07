<?php

namespace App\Services\Email\Campaign;

use App\Models\Business;

class TemplateRendererService
{
    /**
     * Render Subject
     */
    public function renderSubject(
        string $subject,
        Business $business
    ): string {
        return $this->replaceVariables(
            $subject,
            $business
        );
    }

    /**
     * Render HTML
     */
    public function renderHtml(
        string $html,
        Business $business
    ): string {
        return $this->replaceVariables(
            $html,
            $business
        );
    }

    /**
     * Render Plain Text
     */
    public function renderPlainText(
        ?string $text,
        Business $business
    ): ?string {

        if (!$text) {
            return null;
        }

        return $this->replaceVariables(
            $text,
            $business
        );
    }

    /**
     * Replace Variables
     */
    protected function replaceVariables(
        string $content,
        Business $business
    ): string {

        $variables = [

            '{{business_name}}' => $business->business_name,

            '{{website}}' => $business->website,

            '{{email}}' => $business->email,

            '{{phone}}' => $business->phone,

            '{{category}}' => $business->category,

            '{{address}}' => $business->address,

            '{{city}}' => $business->city,

            '{{state}}' => $business->state,

            '{{zip_code}}' => $business->zip_code,

            '{{country}}' => $business->country,

            '{{today}}' => now()->format('d M Y'),

            '{{current_year}}' => now()->year,

        ];

        return str_replace(

            array_keys($variables),

            array_values($variables),

            $content

        );
    }
}