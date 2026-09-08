<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('node_heartbeats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('node_id')->constrained()->cascadeOnDelete();
            $table->string('agent_version')->nullable();
            $table->string('hostname')->nullable();
            $table->string('os')->nullable();
            $table->unsignedInteger('cpu_usage_percent')->nullable();
            $table->unsignedInteger('memory_usage_percent')->nullable();
            $table->unsignedInteger('disk_usage_percent')->nullable();
            $table->unsignedInteger('active_tests')->default(0);
            $table->unsignedInteger('uptime_seconds')->nullable();
            $table->json('load_average')->nullable();
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->index(['node_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('node_heartbeats');
    }
};
