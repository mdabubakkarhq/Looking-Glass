<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nodes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('city')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('provider')->nullable();
            $table->string('asn')->nullable();
            $table->string('ipv4')->nullable();
            $table->string('ipv6')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('uplink_mbps')->nullable();
            $table->string('status')->default('offline'); // online, offline, maintenance, error
            $table->boolean('maintenance')->default(false);
            $table->boolean('public')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('download_host')->nullable();
            $table->string('agent_version')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'public']);
            $table->index('country_code');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nodes');
    }
};
