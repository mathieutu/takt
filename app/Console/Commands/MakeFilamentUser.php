<?php

namespace App\Console\Commands;

use App\Enums\AccountType;
use App\Models\User;
use Filament\Commands\MakeUserCommand;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class MakeFilamentUser extends MakeUserCommand
{
    protected function getUserData(): array
    {
        $user_data = parent::getUserData();

        $this->options = [
            ...$this->options,
            ...$user_data
        ];

        return $user_data;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        parent::handle();
        /**
         * @var \App\Models\Account
         */
        $inserted = static::getUserModel()::latest('created_at')->firstOrFail();
        throw_if(
            $inserted->user()->exists()
            || $inserted->organization()->exists(),
            "The created account has already been registered as a user/organization."
        );

        $names = collect(preg_split('/\s+/', $this->getUserData()['name']));
        $first_name = "Unknown";
        $last_name = "";

        if ($names->isNotEmpty()) {
            $first_name = $names->shift();
            $last_name = $names->join(' ');
        }

        User::create([
            'id' => $inserted->id,
            'first_name' => $first_name,
            'last_name' => $last_name
        ]);
        $inserted->update([
            'type' => AccountType::User
        ]);

        return 1;
    }
}
