<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->string('hostname')->nullable()->after('name');
            $table->boolean('ipv4_enabled')->default(true)->after('ipv6');
            $table->boolean('ipv6_enabled')->default(false)->after('ipv4_enabled');
            $table->boolean('latency_enabled')->default(true)->after('ipv6_enabled');
            $table->boolean('iperf3_enabled')->default(false)->after('latency_enabled');
            $table->unsignedSmallInteger('iperf3_port')->nullable()->after('iperf3_enabled');
            $table->string('iperf3_status')->default('unavailable')->after('iperf3_port'); // available, busy, maintenance, unavailable
            $table->timestamp('last_ipv4_health_check_at')->nullable()->after('last_seen_at');
            $table->timestamp('last_ipv6_health_check_at')->nullable()->after('last_ipv4_health_check_at');
            $table->timestamp('last_iperf3_health_check_at')->nullable()->after('last_ipv6_health_check_at');

            $table->index('hostname');
        });
    }

    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropIndex(['hostname']);
            $table->dropColumn([
                'hostname',
                'ipv4_enabled',
                'ipv6_enabled',
                'latency_enabled',
                'iperf3_enabled',
                'iperf3_port',
                'iperf3_status',
                'last_ipv4_health_check_at',
                'last_ipv6_health_check_at',
                'last_iperf3_health_check_at',
            ]);
        });
    }
};
