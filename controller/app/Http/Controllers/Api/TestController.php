<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTestRequest;
use App\Models\NetworkTest;
use App\Services\SseStreamService;
use App\Services\TestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TestController extends Controller
{
    public function __construct(
        private TestService $testService,
        private SseStreamService $sseService,
    ) {}

    public function store(StoreTestRequest $request): JsonResponse
    {
        $visitorHash = $this->testService->hashVisitor($request->ip());

        if ($this->testService->isRateLimited($visitorHash, $request->input('node_id'))) {
            return response()->json([
                'message' => 'Rate limit exceeded. Please try again later.',
                'error' => 'rate_limited',
            ], 429);
        }

        $targetValidation = $this->testService->validateTarget($request->input('target'), $visitorHash);
        if (!$targetValidation['valid']) {
            return response()->json([
                'message' => $targetValidation['message'],
                'error' => 'invalid_target',
            ], 422);
        }

        try {
            $test = $this->testService->createTest(
                nodeSlug: $request->input('node_id'),
                testType: $request->input('test_type'),
                target: $request->input('target'),
                ipFamily: $request->input('ip_family', 'auto'),
                visitorHash: $visitorHash,
            );

            return response()->json([
                'data' => [
                    'id' => $test->uuid,
                    'node_id' => $test->node->slug,
                    'test_type' => $test->test_type,
                    'target' => $test->target,
                    'ip_family' => $test->ip_family,
                    'status' => $test->status,
                    'stream_url' => "/api/v1/tests/{$test->uuid}/stream",
                    'created_at' => $test->created_at->toISOString(),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'test_creation_failed',
            ], 422);
        }
    }

    public function show(NetworkTest $test): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $test->uuid,
                'node_id' => $test->node->slug,
                'test_type' => $test->test_type,
                'target' => $test->target,
                'resolved_ip' => $test->resolved_ip,
                'ip_family' => $test->ip_family,
                'status' => $test->status,
                'started_at' => $test->started_at?->toISOString(),
                'completed_at' => $test->completed_at?->toISOString(),
                'runtime_ms' => $test->runtime_ms,
                'packet_loss' => $test->packet_loss,
                'latency_min' => $test->latency_min,
                'latency_avg' => $test->latency_avg,
                'latency_max' => $test->latency_max,
                'hop_count' => $test->hop_count,
                'error_code' => $test->error_code,
                'error_message' => $test->error_message,
                'created_at' => $test->created_at->toISOString(),
            ],
        ]);
    }

    public function stream(Request $request, NetworkTest $test): StreamedResponse
    {
        return response()->stream(function () use ($test) {
            $this->sseService->streamTest($test);
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
