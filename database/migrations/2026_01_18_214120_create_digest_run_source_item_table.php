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
        Schema::create('digest_run_source_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('digest_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->nullable(); // ordering in the digest
            $table->timestamps();

            $table->unique(['digest_run_id', 'source_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digest_run_source_item');
    }
};
