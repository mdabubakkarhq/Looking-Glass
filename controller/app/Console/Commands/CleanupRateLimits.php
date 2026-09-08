<?php

namespace App\Console\Commands;

use App\Models\RateLimitEvent;
use Illuminate\Console\Command;

class CleanupRateLimits extends Command
{
    protected $signature = 'lg:cleanup-rate-limits';
    protected $description = 'Remove old rate limit events';

    public function handle(): int
    {
        $retentionDays = config('looking-glass.log_retention_days', 30);
        $cutoff = now()->subDays($retentionDays);

        $deleted = RateLimitEvent::where('created_at', '<', $cutoff)->delete();

        $this->info("Cleaned up {$deleted} rate limit event(s) older than {$retentionDays} days.");

        return self::SUCCESS;
    }
}
