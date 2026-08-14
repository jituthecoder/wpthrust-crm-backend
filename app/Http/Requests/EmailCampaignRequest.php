<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmailCampaignRequest extends FormRequest
{
    /**
     * Authorize request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation Rules
     */
    public function rules(): array
    {

        
        return [

            /*
            |--------------------------------------------------------------------------
            | Basic
            |--------------------------------------------------------------------------
            */

            'name' => [
                'required',
                'string',
                'max:255'
            ],

            'description' => [
                'nullable',
                'string'
            ],

            /*
            |--------------------------------------------------------------------------
            | Template
            |--------------------------------------------------------------------------
            */

            'email_template_id' => [
                'required',
                'exists:email_templates,id'
            ],

            /*
            |--------------------------------------------------------------------------
            | Schedule
            |--------------------------------------------------------------------------
            */

            'scheduled_at' => [
                'nullable',
                'date'
            ],

            /*
            |--------------------------------------------------------------------------
            | Senders
            |--------------------------------------------------------------------------
            */

            'senders' => [
                'required',
                'array',
                'min:1'
            ],

            'senders.*' => [
                'exists:email_senders,id'
            ],

            /*
            |--------------------------------------------------------------------------
            | Businesses
            |--------------------------------------------------------------------------
            */

            'businesses' => [
                'nullable',
                'array',
            ],

            'businesses.*' => [
                'exists:businesses,id'
            ],

            'sequence_steps' => [
                'nullable',
                'array',
            ],

            'auto_sync_enabled' => [
                'nullable',
                'boolean',
            ],

            'auto_sync_criteria' => [
                'nullable',
                'array',
            ],

        ];
    }

    /**
     * Validation Messages
     */
    public function messages(): array
    {
        return [

            'name.required' =>
                'Campaign name is required.',

            'email_template_id.required' =>
                'Please select an email template.',

            'senders.required' =>
                'Please select at least one sender.',

            'businesses.required' =>
                'Please select at least one business.',

        ];
    }

    /**
     * Prepare Data Before Validation
     */
    // protected function prepareForValidation(): void
    // {
    //     dd($this->all());
    // }
}