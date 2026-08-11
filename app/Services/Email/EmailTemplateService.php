<?php

namespace App\Services\Email;

use App\Models\EmailTemplate;
use App\Models\EmailTemplateVersion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmailTemplateService
{
    /**
     * Create Template
     */
    public function create(array $data): EmailTemplate
    {
        return DB::transaction(function () use ($data) {

            $template = EmailTemplate::create([

                'organization_id' => Auth::user()?->organization_id ?? $data['organization_id'] ?? 1,

                'name' => $data['name'],

                'template_type' => $data['template_type'],

                'category' => $data['category'] ?? null,

                'status' => 'draft',

                'created_by' => Auth::id(),

            ]);

            $version = EmailTemplateVersion::create([

                'email_template_id' => $template->id,

                'version' => 1,

                'subject' => $data['subject'],

                'html' => $data['html'],

                'plain_text' => $data['plain_text'] ?? null,

                'changelog' => $data['changelog'] ?? 'Initial Version',

                'created_by' => Auth::id(),

            ]);

            $template->update([

                'current_version_id' => $version->id

            ]);

            return $template->load([
                'currentVersion',
                'versions'
            ]);

        });
    }

    /**
     * Update Template
     * Creates New Version
     */
    public function update(
        EmailTemplate $template,
        array $data
    ): EmailTemplate
    {
        return DB::transaction(function () use (
            $template,
            $data
        ) {

            $latestVersion = $template
                ->versions()
                ->max('version');

            $version = EmailTemplateVersion::create([

                'email_template_id' => $template->id,

                'version' => $latestVersion + 1,

                'subject' => $data['subject'],

                'html' => $data['html'],

                'plain_text' => $data['plain_text'] ?? null,

                'changelog' => $data['changelog'] ?? null,

                'created_by' => Auth::id(),

            ]);

            $template->update([

                'current_version_id' => $version->id

            ]);

            return $template->fresh()->load([
                'currentVersion',
                'versions'
            ]);

        });
    }

    /**
     * Publish Template
     */
    public function publish(
        EmailTemplate $template
    ): EmailTemplate
    {
        return DB::transaction(function () use ($template) {

            EmailTemplateVersion::where(
                'email_template_id',
                $template->id
            )->update([

                'is_published' => false,

                'published_at' => null,

                'published_by' => null,

            ]);

            $version = $template->currentVersion;

            $version->update([

                'is_published' => true,

                'published_at' => now(),

                'published_by' => Auth::id(),

            ]);

            $template->update([

                'status' => 'published'

            ]);

            return $template->fresh()->load([
                'currentVersion'
            ]);

        });
    }

    /**
     * Duplicate Template
     */
    public function duplicate(
        EmailTemplate $template
    ): EmailTemplate
    {
        return DB::transaction(function () use ($template) {

            $newTemplate = EmailTemplate::create([

                'name' => $template->name . ' Copy',

                'template_type' => $template->template_type,

                'category' => $template->category,

                'status' => 'draft',

                'created_by' => Auth::id(),

            ]);

            $current = $template->currentVersion;

            $version = EmailTemplateVersion::create([

                'email_template_id' => $newTemplate->id,

                'version' => 1,

                'subject' => $current->subject,

                'html' => $current->html,

                'plain_text' => $current->plain_text,

                'changelog' => 'Duplicated Template',

                'created_by' => Auth::id(),

            ]);

            $newTemplate->update([

                'current_version_id' => $version->id

            ]);

            return $newTemplate->load([
                'currentVersion'
            ]);

        });
    }

    /**
     * Delete Template
     */
    public function delete(
        EmailTemplate $template
    ): bool
    {
        return $template->delete();
    }
}