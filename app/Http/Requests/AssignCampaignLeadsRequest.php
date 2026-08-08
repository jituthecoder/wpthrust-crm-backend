<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignCampaignLeadsRequest extends FormRequest
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
            | Businesses
            |--------------------------------------------------------------------------
            */

            'businesses' => [
                'required',
                'array',
                'min:1',
            ],

            'businesses.*' => [
                'integer',
                'exists:businesses,id',
            ],

        ];
    }

    /**
     * Validation Messages
     */
    public function messages(): array
    {
        return [

            'businesses.required' =>
                'Please select at least one business.',

            'businesses.array' =>
                'Businesses must be provided as an array.',

            'businesses.min' =>
                'Please select at least one business.',

            'businesses.*.exists' =>
                'One or more selected businesses do not exist.',

        ];
    }
}