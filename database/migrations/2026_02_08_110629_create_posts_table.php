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

            $table->enum('type', ['supply', 'demand']);

            $table->date('scheduled_date')->nullable();
            $table->enum('time_slot', ['morning', 'afternoon', 'evening'])->default('morning')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(
                ['type', 'created_at'],
                'idx_posts_type_created'
            );
            $table->index('scheduled_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
