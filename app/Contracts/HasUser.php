<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\Relation;

/*
 * @property-read User $user
 */
interface HasUser
{
    public function user(): Relation;
}
