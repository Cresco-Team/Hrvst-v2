<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variety_id')->constrained()->cascadeOnDelete();

            $table->unsignedBigInteger('postable_id');
            $table->string('postable_type');

            $table->string('title')->nullable();
            $table->decimal('quantity_kg', 8, 2);
            $table->decimal('offered_price', 8, 2)->nullable();
            $table->enum('price_flag', ['Low', 'Fair', 'High'])->default('Fair');
            $table->enum('status', ['Ongoing', 'Archived', 'Fulfilled'])->default('Ongoing');
            $table->timestamps();

            $table->index(['postable_type', 'postable_id']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
