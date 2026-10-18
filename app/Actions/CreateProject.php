<?php

namespace App\Actions;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateProject
{
    /**
     * Creates a project for an existing client (`client_id`), or for a new one created inline when
     * `client_id` is blank (`client_name` + `client_rate`). `start_date` defaults to today.
     *
     * @param  array<string, mixed>  $input
     */
    public function __invoke(User $user, array $input): Project
    {
        $isNewClient = ! ($input['client_id'] ?? null);

        $data = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'daily_rate' => ['integer', 'min:0'],
            'max_month_budget' => ['nullable', 'integer', 'min:0'],
            'max_total_budget' => ['nullable', 'integer', 'min:1'],
            'start_date' => ['nullable', 'date'],
            ...(! $isNewClient ? [
                'client_id' => ['required', 'uuid', Rule::exists('clients', 'id')->where('user_id', $user->id)],
            ] : [
                // Used when creating a new client inline
                'client_name' => ['required', 'string', 'max:255'],
                'client_rate' => ['required', 'numeric', 'min:0'],
            ]),
        ])->validate();

        if ($isNewClient) {
            $data['client_id'] = $user->clients()->create([
                'name' => $data['client_name'],
                'daily_rate' => $data['client_rate'],
            ])->id;
        }

        return Project::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'daily_rate' => $data['daily_rate'] ?? 0,
            'max_month_budget' => $data['max_month_budget'] ?? null,
            'max_total_budget' => $data['max_total_budget'] ?? null,
            'client_id' => $data['client_id'],
            'start_date' => $data['start_date'] ?? today(),
        ]);
    }
}
