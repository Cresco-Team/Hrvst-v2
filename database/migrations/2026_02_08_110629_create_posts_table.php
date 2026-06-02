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

            // supply: growing → ongoing → fulfilled/archived
            // demand: ongoing → fulfilled/archived
            $table->string('status')->default('growing')->index();

            $table->string('target_month', 7)->nullable();  // "YYYY-MM", supply only
            $table->date('scheduled_date')->nullable();        // set on harvest for supply, creation for demand
            $table->enum('time_slot', ['morning', 'afternoon', 'evening'])->default('morning')->nullable();

            $table->decimal('estimated_total_weight', 12, 2)->nullable(); // farmer's pre-harvest estimate

            $table->unsignedInteger('hearts_count')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index(
                ['vegetable_id', 'type', 'status', 'created_at'],
                'idx_posts_vegetable_type_status_created'
            );
            $table->index('scheduled_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
