<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin111111@kanmo.local'],
            [
                'nip' => '0000111111',
                'name' => 'Admin KMS',
                'email' => 'admin111111@kanmo.local',
                'password' => Hash::make('123456'),
                'role' => 'admin',
                'department' => 'KMS Admin',
                'division' => 'KMS',
                'entity' => 'Kanmo',
                'active' => true,
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'employee21619@kanmo.local'],
            [
                'nip' => '0000021619',
                'name' => 'Fairuz Trideas Hilmy',
                'email' => 'employee21619@kanmo.local',
                'password' => Hash::make('123456'),
                'role' => 'employee',
                'department' => 'Store',
                'division' => 'Human Resources',
                'entity' => 'Kanmo',
                'active' => true,
            ]
        );
    }
}
