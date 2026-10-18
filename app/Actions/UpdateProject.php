<?php

namespace App\Actions;

use App\Models\Project;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateProject
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function __invoke(Project $project, array $input): Project
    {
        $data = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'daily_rate' => ['nullable', 'integer', 'min:0'],
            'daily_rate_effective_date' => ['nullable', 'date'],
            'max_month_budget' => ['nullable', 'integer', 'min:0'],
            'monthly_budget_effective_date' => ['nullable', 'date'],
            'max_total_budget' => ['nullable', 'integer', 'min:1'],
            'client_id' => ['required', Rule::exists('clients', 'id')->where('user_id', $project->user->id)],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
        ])->validate();

        // A value defaults its effective date to today when left blank, and a date alone defaults its
        // value to 0 (a valid, if unusual, daily rate) — for the budget specifically, 0 is normalized
        // to "unlimited" instead (see setMonthlyBudgetFrom).
        if (($data['daily_rate'] ?? null) !== null || ($data['daily_rate_effective_date'] ?? null) !== null) {
            $project->setDailyRateFrom($data['daily_rate'] ?? 0, $data['daily_rate_effective_date'] ?? today());
        }

        if (($data['max_month_budget'] ?? null) !== null || ($data['monthly_budget_effective_date'] ?? null) !== null) {
            $project->setMonthlyBudgetFrom($data['max_month_budget'] ?? 0, $data['monthly_budget_effective_date'] ?? today());
        }

        $project->update(Arr::only($data, [
            'name', 'description', 'client_id', 'start_date', 'end_date', 'max_total_budget',
        ]));

        return $project;
    }
}
