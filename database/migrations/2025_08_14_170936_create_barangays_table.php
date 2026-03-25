<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barangays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->constrained();

            $table->string('name');

            $table->index(['name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barangays');
    }
};
