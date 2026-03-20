<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variety_monthly_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variety_id')->constrained()->cascadeOnDelete();

            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');

            $table->decimal('supply_archived_kg', 10, 2)->default(0);
            $table->decimal('supply_fulfilled_kg', 10, 2)->default(0);
            $table->decimal('demand_archived_kg', 10, 2)->default(0);
            $table->decimal('demand_fulfilled_kg', 10, 2)->default(0);

            $table->timestamps();

            $table->unique(['variety_id', 'year', 'month']);

            $table->index(['variety_id', 'year', 'month'], 'idx_vms_variety_year_month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variety_monthly_stats');
    }
};
