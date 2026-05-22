<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            AccountSeeder::class,       // Users
            ClientSeeder::class,        // Clients (dépend de Users)
            ProjectSeeder::class,       // Projects (dépend de Clients)
            ActivityTimeSeeder::class,  // ActivityTimes (dépend de Projects)
            ViewSeeder::class,          // Views (dépend de Projects)
            ShareSeeder::class,         // Shares polymorphiques (dépend de Projects et Clients)
            SharedProjectSeeder::class, // SharedProjects (dépend de Accounts et Projects)
            SharedClientSeeder::class,  // SharedClients (dépend de Accounts et Clients)
        ]);
    }
}
