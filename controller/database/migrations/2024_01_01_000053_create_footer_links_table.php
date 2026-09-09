<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('footer_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('footer_section_id')->constrained('footer_sections')->cascadeOnDelete();
            $table->string('label');
            $table->string('url');
            $table->boolean('external')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['footer_section_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('footer_links');
    }
};