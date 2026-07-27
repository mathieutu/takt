<?php

namespace Database\Seeders;

use App\Actions\CreateDemoData;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(CreateDemoData $createDemoData): void
    {
        $createDemoData();
    }
}
