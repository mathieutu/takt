<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        // Users
        $usersData = [
            [
                'id' => '11111111-1111-1111-1111-111111111111',
                'email' => 'alice.martin@example.com',
                'first_name' => 'Alice',
                'last_name' => 'Martin',
            ],
            [
                'id' => '22222222-2222-2222-2222-222222222222',
                'email' => 'bob.dupont@example.com',
                'first_name' => 'Bob',
                'last_name' => 'Dupont',
            ],
            [
                'id' => '33333333-3333-3333-3333-333333333333',
                'email' => 'claire.bernard@example.com',
                'first_name' => 'Claire',
                'last_name' => 'Bernard',
            ],
        ];

        foreach ($usersData as $data) {
            $account = Account::create([
                'id' => $data['id'],
                'type' => 'user',
                'email' => $data['email'],
                'password' => Hash::make('password'),
            ]);

            User::create([
                'id' => $account->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
            ]);
        }

        // Organizations
        $orgsData = [
            [
                'id' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
                'email' => 'contact@techcorp.com',
                'name' => 'TechCorp Solutions',
            ],
            [
                'id' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb',
                'email' => 'info@associationverte.org',
                'name' => 'Association Verte',
            ],
        ];

        foreach ($orgsData as $data) {
            $account = Account::create([
                'id' => $data['id'],
                'type' => 'organization',
                'email' => $data['email'],
                'password' => Hash::make('password'),
            ]);

            Organization::create([
                'id' => $account->id,
                'name' => $data['name'],
            ]);
        }
    }
}
