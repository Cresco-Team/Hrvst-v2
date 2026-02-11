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

            $table->decimal('weight_kg', 8, 2);
            $table->decimal('asking_price', 8, 2)->nullable();

            $table->date('expiration_date')->nullable();

            $table->string('image_path')->nullable();

            $table->enum('status', ['available', 'archived'])->default('available');

            $table->timestamps();

            $table->index(['farmer_id', 'status', 'expiration_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plantings');
    }
};
