<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_test_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_test_id')->constrained('network_tests')->cascadeOnDelete();
            $table->string('event_type'); // started, output, statistics, complete, error
            $table->longText('data')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['network_test_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_test_events');
    }
};
