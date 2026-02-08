<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farmer_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained('farmer_profiles')->cascadeOnDelete();
            $table->foreignId('variety_id')->constrained()->cascadeOnDelete();

            $table->string('image_path');
            $table->decimal('quantity_kg', 8, 2);
            $table->decimal('price_asking', 8, 2);
            $table->date('expiration_date');

            $table->enum('status', ['active', 'expired', 'archived'])->default('active');

            $table->timestamps();

            $table->index(['farmer_id', 'status', 'expiration_date']);
            $table->index(['variety_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmer_offerings');
    }
};
