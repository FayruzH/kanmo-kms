<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SopCategorySeeder::class,
            SopDepartmentSeeder::class,
            SopSourceAppSeeder::class,
            SettingSeeder::class,
        ]);
    }
}
