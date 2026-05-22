<?php

namespace App\Http\Requests;

use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $account = Account::authenticated();

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'daily_rate' => ['nullable', 'numeric', 'min:0'],
            'client_id' => ['required', Rule::exists('clients', 'id')->where('user_id', $account->id)],
            // Used when creating a new client inline
            'client_name' => ['required_if:client_id,new', 'string', 'max:255'],
            'client_rate' => ['required_if:client_id,new', 'numeric', 'min:0'],
        ];
    }
}
