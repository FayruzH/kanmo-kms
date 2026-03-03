<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SopSourceAppSeeder extends Seeder
{
    public function run(): void
    {
        $apps = [
            'SharePoint',
            'HRIS',
            'Google Drive',
            'Retail System',
            'Internal Portal'
        ];

        foreach ($apps as $name) {
            DB::table('sop_source_apps')->updateOrInsert(
                ['name' => $name],
                ['active' => true]
            );
        }
    }
}
