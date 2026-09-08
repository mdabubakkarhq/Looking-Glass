<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('download_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('node_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('filename');
            $table->unsignedBigInteger('size_bytes');
            $table->string('size_label'); // 100MB, 1GB, 5GB, 10GB
            $table->string('url');
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['node_id', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('download_files');
    }
};
