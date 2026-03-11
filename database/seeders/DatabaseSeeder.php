<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            SopCategorySeeder::class,
            SopDepartmentSeeder::class,
            SopSourceAppSeeder::class,
            SettingSeeder::class,
        ]);
    }
}
