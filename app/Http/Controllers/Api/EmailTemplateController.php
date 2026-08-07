<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Services\Email\EmailTemplateService;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    protected EmailTemplateService $service;

    public function __construct(EmailTemplateService $service)
    {
        $this->service = $service;
    }

    /**
     * List Templates
     */
    public function index(Request $request)
    {
        $query = EmailTemplate::with([
            'currentVersion',
            'creator'
        ]);

        if ($request->filled('search')) {

            $query->where(function ($q) use ($request) {

                $q->where(
                    'name',
                    'LIKE',
                    '%' . $request->search . '%'
                );

            });

        }

        if ($request->filled('status')) {

            $query->where(
                'status',
                $request->status
            );

        }

        if ($request->filled('template_type')) {

            $query->where(
                'template_type',
                $request->template_type
            );

        }

        $templates = $query
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $templates
        ]);
    }

    /**
     * Create Template
     */
    public function store(Request $request)
    {
        $validated = $request->validate([

            'name' => 'required|string|max:255',

            'template_type' => 'required|in:cold_email,follow_up,manual,transactional',

            'category' => 'nullable|string|max:255',

            'subject' => 'required|string|max:255',

            'html' => 'required|string',

            'plain_text' => 'nullable|string',

            'changelog' => 'nullable|string',

        ]);

        $template = $this->service->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Template created successfully.',
            'data' => $template
        ],201);
    }

    /**
     * Show Template
     */
    public function show(EmailTemplate $emailTemplate)
    {
        return response()->json([

            'success'=>true,

            'data'=>$emailTemplate->load([

                'currentVersion',

                'versions.creator',

                'creator'

            ])

        ]);
    }

    /**
     * Update Template
     * Creates New Version
     */
    public function update(
        Request $request,
        EmailTemplate $emailTemplate
    )
    {

        $validated = $request->validate([

            'subject'=>'required|string|max:255',

            'html'=>'required|string',

            'plain_text'=>'nullable|string',

            'changelog'=>'nullable|string',

        ]);

        $template = $this->service->update(
            $emailTemplate,
            $validated
        );

        return response()->json([

            'success'=>true,

            'message'=>'New version created.',

            'data'=>$template

        ]);

    }

    /**
     * Delete Template
     */
    public function destroy(
        EmailTemplate $emailTemplate
    )
    {

        $this->service->delete(
            $emailTemplate
        );

        return response()->json([

            'success'=>true,

            'message'=>'Template deleted successfully.'

        ]);

    }

    /**
     * Publish Template
     */
    public function publish(
        EmailTemplate $emailTemplate
    )
    {

        $template = $this->service->publish(
            $emailTemplate
        );

        return response()->json([

            'success'=>true,

            'message'=>'Template published.',

            'data'=>$template

        ]);

    }

    /**
     * Duplicate Template
     */
    public function duplicate(
        EmailTemplate $emailTemplate
    )
    {

        $template = $this->service->duplicate(
            $emailTemplate
        );

        return response()->json([

            'success'=>true,

            'message'=>'Template duplicated.',

            'data'=>$template

        ]);

    }

    /**
     * Version History
     */
    public function versions(
        EmailTemplate $emailTemplate
    )
    {

        return response()->json([

            'success'=>true,

            'data'=>$emailTemplate
                ->versions()
                ->with([
                    'creator',
                    'publisher'
                ])
                ->get()

        ]);

    }
}