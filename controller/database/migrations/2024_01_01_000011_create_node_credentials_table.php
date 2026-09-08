<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('node_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('node_id')->constrained()->cascadeOnDelete();
            $table->string('node_key_id')->unique(); // Public identifier for the node
            $table->string('node_secret'); // Hashed shared secret
            $table->string('agent_ip')->nullable();
            $table->timestamp('last_authenticated_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('node_key_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('node_credentials');
    }
};
