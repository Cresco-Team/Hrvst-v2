<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_requests', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('phone_number');
            $table->string('email')->nullable();
            $table->string('role');
            $table->string('pin');

            $table->foreignId('municipality_id')->nullable()->constrained();
            $table->foreignId('barangay_id')->nullable()->constrained();
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();

            $table->string('status')->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'phone_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_requests');
    }
};
