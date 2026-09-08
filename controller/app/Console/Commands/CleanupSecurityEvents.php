<?php

namespace App\Console\Commands;

use App\Models\SecurityEvent;
use Illuminate\Console\Command;

class CleanupSecurityEvents extends Command
{
    protected $signature = 'lg:cleanup-security-events';
    protected $description = 'Remove old security events based on retention policy';

    public function handle(): int
    {
        $retentionDays = config('looking-glass.log_retention_days', 30);
        $cutoff = now()->subDays($retentionDays);

        $deleted = SecurityEvent::where('created_at', '<', $cutoff)->delete();

        $this->info("Cleaned up {$deleted} security event(s) older than {$retentionDays} days.");

        return self::SUCCESS;
    }
}
