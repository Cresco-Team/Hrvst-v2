<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vegetable_monthly_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vegetable_id')->constrained()->cascadeOnDelete();

            $table->date('period_date');

            $table->decimal('supply_archived_kg', 10, 2)->default(0);
            $table->decimal('supply_fulfilled_kg', 10, 2)->default(0);
            $table->decimal('demand_archived_kg', 10, 2)->default(0);
            $table->decimal('demand_fulfilled_kg', 10, 2)->default(0);

            $table->timestamps();

            $table->unique(['vegetable_id', 'period_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vegetable_monthly_stats');
    }
};
