<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dealer_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dealer_id')->constrained('dealer_profiles')->cascadeOnDelete();

            $table->date('transaction_date');
            $table->enum('status', ['open', 'fulfilled', 'expired'])->default('open');

            $table->timestamps();

            $table->index(['dealer_id', 'status', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dealer_requests');
    }
};
