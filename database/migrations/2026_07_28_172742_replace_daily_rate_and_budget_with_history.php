<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->json('daily_rates')->nullable()->after('daily_rate');
            $table->json('monthly_budgets')->nullable()->after('max_month_budget');
        });

        foreach (DB::table('projects')->get() as $project) {
            $effectiveFrom = $project->start_date ?? now()->toDateString();

            DB::table('projects')->where('id', $project->id)->update([
                'daily_rates' => json_encode([$effectiveFrom => $project->daily_rate]),
                'monthly_budgets' => $project->max_month_budget !== null
                    ? json_encode([$effectiveFrom => $project->max_month_budget])
                    : null,
            ]);
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['daily_rate', 'max_month_budget']);
        });

        Schema::table('projects', function (Blueprint $table) {
            // A project always has a rate; monthly_budgets stays nullable — no budget is a valid state.
            $table->json('daily_rates')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedInteger('daily_rate')->nullable()->after('client_id');
            $table->unsignedInteger('max_month_budget')->nullable()->after('daily_rate');
        });

        foreach (DB::table('projects')->get() as $project) {
            $dailyRates = collect(json_decode($project->daily_rates ?? '{}', true))->sortKeys();
            $monthlyBudgets = collect(json_decode($project->monthly_budgets ?? '{}', true))->sortKeys();

            DB::table('projects')->where('id', $project->id)->update([
                'daily_rate' => $dailyRates->last() ?? 0,
                'max_month_budget' => $monthlyBudgets->last(),
            ]);
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedInteger('daily_rate')->nullable(false)->change();
            $table->dropColumn(['daily_rates', 'monthly_budgets']);
        });
    }
};
