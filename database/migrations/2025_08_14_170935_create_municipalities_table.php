<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('municipalities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('province_id')->constrained();

            $table->string('name');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 10, 8);

            $table->index(['name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipalities');
    }
};
