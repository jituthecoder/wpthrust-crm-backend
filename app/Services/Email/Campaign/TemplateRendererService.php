<?php

namespace App\Services\Email\Campaign;

use App\Models\Business;
use App\Services\Email\TemplateVariableService;

class TemplateRendererService
{
    protected TemplateVariableService $variableService;

    public function __construct(TemplateVariableService $variableService)
    {
        $this->variableService = $variableService;
    }

    /**
     * Render Subject
     */
    public function renderSubject(
        string $subject,
        Business $business
    ): string {
        return $this->variableService->render(
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
        return $this->variableService->render(
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

        return $this->variableService->render(
            $text,
            $business
        );
    }
}