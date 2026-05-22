<?php

namespace App\Policies;

use App\Models\ActivityTime;
use App\Models\User;

class ActivityTimePolicy
{
    public function update(User $user, ActivityTime $activityTime): bool
    {
        return $user->id === $activityTime->project->client->user_id;
    }

    public function delete(User $user, ActivityTime $activityTime): bool
    {
        return $user->id === $activityTime->project->client->user_id;
    }
}
