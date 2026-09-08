<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type'); // auth_failure, invalid_target, dns_rebind, blocked_network, agent_auth_failure, suspicious_activity
            $table->string('severity')->default('info'); // info, warning, critical
            $table->string('source_ip')->nullable();
            $table->string('visitor_hash')->nullable();
            $table->foreignId('node_id')->nullable()->constrained()->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['event_type', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};
