<?php

namespace App\Services;

use App\Models\NetworkTest;

class SseStreamService
{
    public function streamTest(NetworkTest $test): void
    {
        $this->sendEvent('started', [
            'id' => $test->uuid,
            'test_type' => $test->test_type,
            'target' => $test->target,
            'status' => $test->status,
        ]);

        if ($test->isCompleted() || $test->isFailed()) {
            $this->sendStoredEvents($test);
            return;
        }

        $this->pollAndStream($test);
    }

    private function pollAndStream(NetworkTest $test): void
    {
        $lastEventId = 0;
        $timeout = config('looking-glass.test_timeout_seconds', 60);
        $startTime = time();

        while ((time() - $startTime) < $timeout) {
            $events = $test->events()->where('id', '>', $lastEventId)->orderBy('id')->get();

            foreach ($events as $event) {
                $lastEventId = $event->id;
                $this->sendEvent($event->event_type, [
                    'data' => $event->data,
                    'occurred_at' => $event->occurred_at->toISOString(),
                ]);

                if (in_array($event->event_type, ['complete', 'error'])) {
                    return;
                }
            }

            $test->refresh();

            if ($test->isCompleted() || $test->isFailed()) {
                $this->sendStoredEvents($test, $lastEventId);
                return;
            }

            usleep(500_000);
        }

        $this->sendEvent('error', ['message' => 'Test timed out', 'error_code' => 'timeout']);
    }

    private function sendStoredEvents(NetworkTest $test, int $afterId = 0): void
    {
        $events = $test->events()
            ->when($afterId > 0, fn ($q) => $q->where('id', '>', $afterId))
            ->orderBy('occurred_at')->get();

        foreach ($events as $event) {
            $this->sendEvent($event->event_type, [
                'data' => $event->data,
                'occurred_at' => $event->occurred_at->toISOString(),
            ]);
        }

        $this->sendEvent('complete', [
            'status' => $test->status,
            'runtime_ms' => $test->runtime_ms,
            'packet_loss' => $test->packet_loss,
            'latency_min' => $test->latency_min,
            'latency_avg' => $test->latency_avg,
            'latency_max' => $test->latency_max,
            'hop_count' => $test->hop_count,
        ]);
    }

    private function sendEvent(string $event, array $data): void
    {
        echo "event: {$event}\ndata: " . json_encode($data) . "\n\n";
        if (ob_get_level() > 0) { ob_flush(); }
        flush();
    }
}
