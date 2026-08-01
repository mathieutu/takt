<?php

use App\Models\Model;
use Illuminate\Support\Facades\DB;

arch('no debug statements are left in the application code')
    ->expect('App')
    ->not->toUse(['dd', 'dump', 'ray', 'var_dump']);

arch('controllers do not query the database directly, they go through models')
    ->expect('App\Http\Controllers')
    ->not->toUse(DB::class);

arch('models extend the base App\Models\Model class')
    ->expect('App\Models')
    ->classes()
    ->toExtend(Model::class)
    ->ignoring('App\Models\Model');
