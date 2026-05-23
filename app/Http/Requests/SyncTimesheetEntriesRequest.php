<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncTimesheetEntriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            '*.coverage' => ['required', 'integer', 'between:0,100'],
            '*.title' => ['nullable', 'string'],
            '*.description' => ['nullable', 'string'],
        ];
    }
}
