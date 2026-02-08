<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dealer_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dealer_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variety_id')->constrained()->cascadeOnDelete();

            $table->decimal('quantity_kg', 8, 2);
            $table->decimal('price_offered', 8, 2);
            $table->enum('price_flag', ['cheap', 'fair', 'high'])->nullable();

            $table->timestamps();

            $table->index(['dealer_request_id', 'variety_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dealer_request_items');
    }
};
