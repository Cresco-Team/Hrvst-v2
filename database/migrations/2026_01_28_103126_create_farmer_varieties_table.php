<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farmer_varieties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained('farmers')->cascadeOnDelete();
            $table->foreignId('vegetable_id')->constrained('vegetables')->cascadeOnDelete();

            $table->string('yield_kg');

            $table->date('date_planted');
            $table->date('expected_harvest_date');
            $table->date('date_harvested');

            $table->enum('status', ['active', 'harvested', 'expired']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmer_vegetable');
    }
};
