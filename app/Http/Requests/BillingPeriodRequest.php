<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BillingPeriodRequest extends FormRequest
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
            'from' => ['nullable', 'required_with:to', 'date_format:Y-m'],
            'to' => ['nullable', 'required_with:from', 'date_format:Y-m', 'after_or_equal:from'],
        ];
    }

    /**
     * An invalid period falls back to the unfiltered page: redirecting "back" on a GET opened
     * directly would loop on the very same invalid URL.
     */
    protected function getRedirectUrl(): string
    {
        return $this->url();
    }

    /**
     * @return array{from: string, to: string}|null
     */
    public function period(): ?array
    {
        $from = $this->validated('from');

        return $from ? ['from' => $from, 'to' => $this->validated('to')] : null;
    }
}
