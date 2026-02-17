<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farmer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('province_id')->constrained();
            $table->foreignId('municipality_id')->constrained();
            $table->foreignId('barangay_id')->constrained();

            $table->boolean('is_approved')->default(false);

            $table->double('latitude');
            $table->double('longitude');

            $table->string('farm_image')->nullable();
            
            $table->timestamps();
            $table->index('is_approved');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmer_profiles');
    }
};
