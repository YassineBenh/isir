<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('source_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained()->cascadeOnDelete();
            $table->string('external_id'); // release id, RSS guid, video id, etc.
            $table->string('title');
            $table->string('url')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->longText('raw_content')->nullable(); // release notes, RSS content, etc.
            $table->json('metadata')->nullable(); // tag, version, author, etc.
            $table->timestamps();

            $table->unique(['source_id', 'external_id']);
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('source_items');
    }
};
