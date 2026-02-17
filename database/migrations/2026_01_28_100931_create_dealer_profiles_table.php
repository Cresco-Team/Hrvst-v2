<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dealer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->boolean('is_approved')->default(false);

            $table->string('document_image')->nullable();

            $table->timestamps();

            $table->index('is_approved');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dealer_profiles');
    }
};
