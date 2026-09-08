<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('node_capabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('node_id')->constrained()->cascadeOnDelete();
            $table->string('feature'); // ping, traceroute, mtr, dns, downloads, iperf3
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('max_concurrent')->nullable();
            $table->unsignedInteger('timeout_seconds')->nullable();
            $table->timestamps();

            $table->unique(['node_id', 'feature']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('node_capabilities');
    }
};
