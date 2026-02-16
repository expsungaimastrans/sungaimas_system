<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DefaultUsersSeeder extends Seeder
{
    public function run(): void
    {
        // kamu bisa ubah password kapan saja
        $users = [
            [
                'name' => 'Owner',
                'username' => 'owner',
                'role' => 'owner',
                'password' => 'Owner@12345',
            ],
            [
                'name' => 'Admin Staff',
                'username' => 'admin',
                'role' => 'admin',
                'password' => 'Admin@12345',
            ],
            [
                'name' => 'Finance Staff',
                'username' => 'finance',
                'role' => 'finance',
                'password' => 'Finance@12345',
            ],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(
                ['username' => $u['username']],
                [
                    'name' => $u['name'],
                    'role' => $u['role'],
                    'password' => Hash::make($u['password']),
                    // email optional kalau field email ada & not null:
                    'email' => ($u['username']).'@local.test',
                ]
            );
        }
    }
}

