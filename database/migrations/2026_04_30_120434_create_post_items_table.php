<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vegetable_id')->constrained()->cascadeOnDelete();

            $table->decimal('quantity_kg', 8, 2);
            $table->enum('status', ['ongoing', 'expired', 'fulfilled'])->default('ongoing');

            $table->index(['vegetable_id', 'status'], 'idx_post_items_vegetable_status');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_items');
    }
};
