<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variety_id')->constrained()->cascadeOnDelete();

            $table->decimal('price_min', 5, 2);
            $table->decimal('price_max', 5, 2);

            $table->date('recorded_at');

            $table->timestamps();

            $table->index(['variety_id', 'recorded_at'], 'idx_variety_recorded');
            $table->index('recorded_at', 'idx_recorded_at');
            $table->unique(['variety_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_histories');
    }
};
