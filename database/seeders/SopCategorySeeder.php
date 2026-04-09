<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SopCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'BUSINESS DEVELOPMENT',
            'DIGITAL, OMNICHANNEL & INTELLIGENT TECHNOLOGY',
            'DISTRIBUTION',
            'FASHION & ACCESSORIES',
            'FINANCE',
            'FOOTWEAR & ACTIVE',
            'HUMAN RESOURCES',
            'LEGAL & COMPLIANCE',
            'LIFESTYLE',
            'MANAGEMENT',
            'MARKETING',
            'MTI',
            'MTI FINANCE BUSINESS PARTNER',
            'MTI LOCAL SOURCING',
            'MTI MARKETING',
            'MTI OPERATIONS',
            'MTI RETAIL',
            'OWN BRAND',
            'PROJECT & MAINTENANCE',
            'SHIPPING',
            'WAREHOUSE',
        ];

        foreach ($categories as $name) {
            DB::table('sop_categories')->updateOrInsert(
                ['name' => $name],
                ['active' => true]
            );
        }
    }
}
