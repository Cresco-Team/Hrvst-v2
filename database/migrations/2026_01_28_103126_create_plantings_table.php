<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plantings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained('farmer_profiles')->cascadeOnDelete();
            $table->foreignId('variety_id')->constrained()->cascadeOnDelete();

            $table->decimal('weight_kg');

            $table->date('date_planted');
            $table->date('expected_harvest_date');
            $table->date('date_harvested')->nullable();

            $table->enum('status', ['active', 'harvested', 'expired', 'cancelled'])->default('active');

            $table->timestamps();

            $table->index(['farmer_id', 'status', 'expected_harvest_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plantings');
    }
};
