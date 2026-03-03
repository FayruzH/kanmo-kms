<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SopCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'HR Policy',
            'IT Procedure',
            'Retail Operation',
            'Finance & Accounting',
            'Warehouse',
            'Corporate Governance'
        ];

        foreach ($categories as $name) {
            DB::table('sop_categories')->updateOrInsert(
                ['name' => $name],
                ['active' => true]
            );
        }
    }
}
