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
        Schema::create('digests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('frequency'); // daily, weekly
            $table->string('timezone')->default('UTC');
            $table->time('send_time')->default('09:00:00');
            $table->unsignedTinyInteger('send_day_of_week')->nullable(); // 0=Sunday..6=Saturday (for weekly)
            $table->boolean('is_enabled')->default(true);
            $table->timestamp('last_successful_run_at')->nullable();
            $table->boolean('ai_enabled')->default(true);
            $table->json('ai_prefs')->nullable(); // custom prompt, model preference, etc.
            $table->timestamps();

            $table->index(['user_id', 'is_enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digests');
    }
};
