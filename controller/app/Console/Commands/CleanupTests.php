<?php

namespace App\Console\Commands;

use App\Models\NetworkTest;
use App\Models\NetworkTestEvent;
use Illuminate\Console\Command;

class CleanupTests extends Command
{
    protected $signature = 'lg:cleanup-tests';
    protected $description = 'Remove old test records and events';

    public function handle(): int
    {
        $retentionDays = config('looking-glass.log_retention_days', 30);
        $cutoff = now()->subDays($retentionDays);

        // Delete events first (foreign key)
        $eventsDeleted = NetworkTestEvent::where('created_at', '<', $cutoff)->delete();

        $testsDeleted = NetworkTest::where('created_at', '<', $cutoff)->delete();

        $this->info("Cleaned up {$testsDeleted} test(s) and {$eventsDeleted} event(s).");

        return self::SUCCESS;
    }
}
