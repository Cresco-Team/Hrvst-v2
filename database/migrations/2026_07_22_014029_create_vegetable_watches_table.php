<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vegetable_watches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vegetable_id')->constrained()->cascadeOnDelete();

            $table->string('viewer_role');

            $table->string('last_notified_band')->nullable();
            $table->timestamp('last_evaluated_at')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'vegetable_id']);
            $table->index(['vegetable_id', 'last_evaluated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vegetable_watches');
    }
};