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
        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // github_repo, rss_feed, youtube_channel, etc.
            $table->string('canonical_key')->unique(); // e.g. "github:laravel/framework", "rss:https://..."
            $table->string('name');
            $table->string('url')->nullable();
            $table->json('config')->nullable(); // type-specific config (repo id, feed URL, channel id, etc.)
            $table->boolean('is_enabled')->default(true);
            $table->timestamp('last_fetched_at')->nullable();
            $table->json('fetch_state')->nullable(); // cursor, etag, last-modified, etc.
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('is_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sources');
    }
};
