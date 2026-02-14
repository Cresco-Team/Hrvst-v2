<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();

            $table->string('reason');
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'reviewed', 'dismissed'])->default('pending');

            $table->timestamps();

            $table->unique(['user_id', 'post_id']);
            $table->index(['post_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_flags');
    }
};
