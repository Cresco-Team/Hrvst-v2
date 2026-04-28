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
            $table->foreignId('vegetable_id')->constrained()->cascadeOnDelete();

            $table->enum('type', ['supply', 'demand']);
            $table->enum('status', ['Ongoing', 'Archived', 'Fulfilled'])->default('Ongoing');
            $table->decimal('quantity_kg', 8, 2);
            $table->decimal('offered_price', 8, 2)->nullable();

            $table->enum('price_flag', ['Low', 'Fair', 'High'])->default('Fair');
            $table->date('scheduled_date')->nullable();
            $table->enum('time_slot', ['morning', 'afternoon', 'evening'])->nullable();

            $table->unsignedInteger('hearts_count')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status']);
            $table->index(
                ['variety_id', 'type', 'status', 'created_at'],
                'idx_posts_variety_type_status_created'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
