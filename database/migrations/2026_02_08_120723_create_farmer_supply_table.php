<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farmer_supplies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained('farmer_profiles')->cascadeOnDelete();

            $table->date('expiration_date');
            $table->timestamps();

            $table->index(['farmer_id', 'expiration_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmer_supplies');
    }
};
