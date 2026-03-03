<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'expiry_threshold_days' => 30,
            'download_policy' => 'generate_cover_pdf', // or open_only
            'reminder_expiring_days' => [30,14,7,1],
            'reminder_expired_interval_days' => 7
        ];

        foreach ($settings as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value_json' => json_encode($value)]
            );
        }
    }
}
