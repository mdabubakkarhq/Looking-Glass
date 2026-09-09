<?php

namespace App\Console\Commands;

use App\Models\NetworkTest;
use App\Models\NetworkTestEvent;
use Illuminate\Console\Command;

class PurgeTests extends Command
{
    protected $signature = 'lg:purge-tests
                            {--days=7 : Delete completed/failed tests older than this many days}
                            {--status= : Only purge tests with this status (completed, failed)}';

    protected $description = 'Purge old completed/failed network tests and their events';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $status = $this->option('status');
        $cutoff = now()->subDays($days);

        $query = NetworkTest::where('created_at', '<', $cutoff)
            ->whereIn('status', $status ? [$status] : ['completed', 'failed']);

        $testIds = $query->pluck('id');

        if ($testIds->isEmpty()) {
            $this->info('No tests to purge.');
            return self::SUCCESS;
        }

        // Delete events first (foreign key)
        $eventsDeleted = NetworkTestEvent::whereIn('network_test_id', $testIds)->delete();
        $testsDeleted = NetworkTest::whereIn('id', $testIds)->delete();

        $this->info("Purged {$testsDeleted} test(s) and {$eventsDeleted} event(s) older than {$days} days.");

        return self::SUCCESS;
    }
}