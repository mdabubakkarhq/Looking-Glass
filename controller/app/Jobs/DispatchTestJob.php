<?php

namespace App\Jobs;

use App\Models\NetworkTest;
use App\Models\Node;
use App\Services\AgentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchTestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        public int $testId,
        public int $nodeId,
    ) {
        $this->onQueue('tests');
    }

    public function handle(AgentService $agentService): void
    {
        $test = NetworkTest::find($this->testId);
        $node = Node::find($this->nodeId);

        if (!$test || !$node) {
            return;
        }

        // Only dispatch if still pending
        if (!$test->isPending()) {
            return;
        }

        $agentService->dispatchTest($test, $node);
    }
}
