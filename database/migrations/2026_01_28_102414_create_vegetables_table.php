<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vegetables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained();

            $table->string('vegetable_name');
            $table->string('variety_name')->nullable();
            $table->string('local_name')->nullable();

            $table->timestamps();

            $table->index(['category_id', 'vegetable_name']);
            $table->unique(['category_id', 'vegetable_name', 'variety_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vegetables');
    }
};
