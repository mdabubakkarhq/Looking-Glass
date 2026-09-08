<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_tests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('node_id')->constrained()->cascadeOnDelete();
            $table->string('test_type'); // ping, traceroute, mtr, dns
            $table->string('target'); // hostname or IP
            $table->string('resolved_ip')->nullable();
            $table->string('ip_family')->default('auto'); // auto, ipv4, ipv6
            $table->string('status')->default('pending'); // pending, running, completed, failed, cancelled, rate_limited
            $table->string('visitor_hash')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('runtime_ms')->nullable();

            // Ping/MTR statistics
            $table->float('packet_loss')->nullable();
            $table->float('latency_min')->nullable();
            $table->float('latency_avg')->nullable();
            $table->float('latency_max')->nullable();
            $table->float('latency_stddev')->nullable();

            // Traceroute/MTR
            $table->unsignedSmallInteger('hop_count')->nullable();

            // Error handling
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index(['node_id', 'status']);
            $table->index(['visitor_hash', 'created_at']);
            $table->index('test_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_tests');
    }
};
