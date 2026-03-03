<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SopDepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            'HRIS',
            'Store Operation',
            'Warehouse',
            'Finance',
            'IT',
            'Corporate'
        ];

        foreach ($departments as $name) {
            DB::table('sop_departments')->updateOrInsert(
                ['name' => $name],
                ['active' => true]
            );
        }
    }
}
