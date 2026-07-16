<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ExportBillingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // authorization is handled by the route middleware / share token
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'project_ids' => ['required', 'array', 'min:1'],
            'project_ids.*' => ['string'],
            'from' => ['required', 'date_format:Y-m'],
            'to' => ['required', 'date_format:Y-m', 'after_or_equal:from'],
        ];
    }
}
