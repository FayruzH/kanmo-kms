<?php

namespace App\Console\Commands;

use App\Services\KepEmployeeSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncKepEmployees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:sync-kep';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync employee accounts from KEP API into local users table';

    /**
     * Execute the console command.
     */
    public function handle(KepEmployeeSyncService $service): int
    {
        $this->info('Fetching employee data from KEP API and syncing users...');

        try {
            $result = $service->sync();
        } catch (Throwable $e) {
            $this->error('Sync failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Total API', 'Created', 'Updated', 'Skipped', 'Admin Skipped'],
            [[
                $result['total'],
                $result['created'],
                $result['updated'],
                $result['skipped'],
                $result['admin_skipped'],
            ]]
        );

        $this->info('KEP employee sync completed.');

        return self::SUCCESS;
    }
}
