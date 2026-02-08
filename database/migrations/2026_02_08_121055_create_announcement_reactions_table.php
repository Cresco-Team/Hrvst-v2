<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcement_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('reactionable'); // reactionable_id, reactionable_type

            // For dealer_requests: 'thumbs_up' or 'thumbs_down'
            // For farmer_offerings: emoji string like '👍', '❤️', '🔥', etc.
            $table->string('reaction_type', 20);

            $table->timestamps();

            $table->unique(['user_id', 'reactionable_id', 'reactionable_type'], 'unique_user_reaction');
            $table->index(['reactionable_id', 'reactionable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_reactions');
    }
};
