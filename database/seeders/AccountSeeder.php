<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'id' => '11111111-1111-1111-1111-111111111111',
                'name' => 'Alice Martin',
                'email' => 'alice.martin@example.com',
            ],
            [
                'id' => '22222222-2222-2222-2222-222222222222',
                'name' => 'Bob Dupont',
                'email' => 'bob.dupont@example.com',
            ],
            [
                'id' => '33333333-3333-3333-3333-333333333333',
                'name' => 'Claire Bernard',
                'email' => 'claire.bernard@example.com',
            ],
        ];

        foreach ($users as $data) {
            User::create($data);
        }
    }
}
