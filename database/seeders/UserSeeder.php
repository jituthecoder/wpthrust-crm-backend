<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin
        User::updateOrCreate(
            [
                'email' => 'admin@wpthrust.com',
            ],
            [
                'name' => 'Super Admin',
                'password' => 'password',
                'role' => 'super_admin',
            ]
        );

        // Sales Executives
        $salesExecutives = [
            [
                'name' => 'Rahul Sharma',
                'email' => 'rahul@wpthrust.com',
            ],
            [
                'name' => 'Amit Kumar',
                'email' => 'amit@wpthrust.com',
            ],
            [
                'name' => 'Priya Singh',
                'email' => 'priya@wpthrust.com',
            ],
            [
                'name' => 'Neha Patel',
                'email' => 'neha@wpthrust.com',
            ],
            [
                'name' => 'Rohit Verma',
                'email' => 'rohit@wpthrust.com',
            ],
        ];

        foreach ($salesExecutives as $user) {
            User::updateOrCreate(
                [
                    'email' => $user['email'],
                ],
                [
                    'name' => $user['name'],
                    'password' => 'password',
                    'role' => 'sales_executive',
                ]
            );
        }
    }
}