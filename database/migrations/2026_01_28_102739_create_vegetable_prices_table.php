<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vegetable_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vegetable_id')->constrained()->cascadeOnDelete();

            $table->decimal('price_min', 5, 2);
            $table->decimal('price_max', 5, 2);

            $table->date('recorded_at');

            $table->timestamps();

            $table->unique(['vegetable_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vegetable_prices');
    }
};
