<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'nicholassinaga@hotmail.com'],
            [
                'name' => 'Nicholas Christhofer',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'basaulisianturi1973@gmail.com'],
            [
                'name' => 'Basauli Sianturi',
                'password' => Hash::make('password'),
                'role' => 'cashier',
            ]
        );
    }
}
