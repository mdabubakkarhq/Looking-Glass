<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rate_limit_events', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_hash')->nullable();
            $table->string('ip_family')->nullable();
            $table->string('endpoint');
            $table->string('reason'); // per_ip, per_node, global, concurrent
            $table->foreignId('node_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['visitor_hash', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_limit_events');
    }
};
