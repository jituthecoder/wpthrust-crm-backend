<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Services\Email\TemplateVariableService;
use Illuminate\Http\Request;

class TemplateVariableController extends Controller
{
    protected TemplateVariableService $service;

    public function __construct(
        TemplateVariableService $service
    ) {
        $this->service = $service;
    }

    /**
     * List Available Variables
     *
     * GET /api/template-variables
     */
    public function index()
    {
        return response()->json([

            'success' => true,

            'data' => $this->service->variables(),

        ]);
    }

    /**
     * Preview Variable Replacement
     *
     * POST /api/template-variables/preview
     */
    public function preview(Request $request)
    {
        $validated = $request->validate([

            'business_id' => 'required|exists:businesses,id',

            'content' => 'required|string',

        ]);

        $business = Business::with('audit')->findOrFail(
            $validated['business_id']
        );

        $content = $this->service->render(

            $validated['content'],

            $business

        );

        return response()->json([

            'success' => true,

            'preview' => $content,

        ]);
    }

    /**
     * Replace Variables (Debug API)
     *
     * POST /api/template-variables/render
     */
    public function render(Request $request)
    {
        $validated = $request->validate([

            'business_id' => 'required|exists:businesses,id',

            'subject' => 'required|string',

            'html' => 'required|string',

        ]);

        $business = Business::with('audit')->findOrFail(
            $validated['business_id']
        );

        return response()->json([

            'success' => true,

            'data' => [

                'subject' => $this->service->render(

                    $validated['subject'],

                    $business

                ),

                'html' => $this->service->render(

                    $validated['html'],

                    $business

                ),

            ]

        ]);
    }
}