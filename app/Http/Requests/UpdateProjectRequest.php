<?php

namespace App\Http\Requests;

use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'daily_rate' => ['nullable', 'numeric', 'min:0'],
            'client_id' => ['required', Rule::exists('clients', 'id')->where('user_id', Account::authenticated()->id)],
        ];
    }
}
