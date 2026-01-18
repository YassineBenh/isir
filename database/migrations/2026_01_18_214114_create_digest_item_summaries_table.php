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
        Schema::create('digest_item_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('digest_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_item_id')->constrained()->cascadeOnDelete();
            $table->text('summary_markdown')->nullable();
            $table->json('summary_json')->nullable(); // key_changes, breaking_changes, highlights, action_items
            $table->string('provider')->nullable(); // openai, anthropic, etc.
            $table->string('model')->nullable();
            $table->string('status')->default('pending'); // pending, completed, failed
            $table->text('error')->nullable();
            $table->timestamps();

            $table->unique(['digest_id', 'source_item_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digest_item_summaries');
    }
};
