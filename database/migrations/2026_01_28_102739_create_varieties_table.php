<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('varieties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vegetable_id')->constrained();

            $table->string('name');
            $table->string('image_path');
            $table->integer('weeks_to_harvest');

            $table->timestamps();

            $table->index('name', 'idx_variety_name');
            $table->index(['vegetable_id', 'name']);
            $table->unique(['vegetable_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('varieties');
    }
};
