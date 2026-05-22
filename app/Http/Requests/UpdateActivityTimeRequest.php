<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActivityTimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'day_coverage' => ['nullable', 'integer', 'between:0,100'],
            'comments' => ['nullable', 'string'],
        ];
    }
}
