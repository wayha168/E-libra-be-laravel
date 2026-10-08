<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dd_trips', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('store_id')->nullable()->constrained('dd_stores')->nullOnDelete();

            $table->string('status')->default('requested'); // requested|accepted|in_progress|completed|cancelled

            $table->decimal('pickup_latitude', 10, 7);
            $table->decimal('pickup_longitude', 10, 7);
            $table->string('pickup_address')->nullable();

            $table->decimal('dropoff_latitude', 10, 7)->nullable();
            $table->decimal('dropoff_longitude', 10, 7)->nullable();
            $table->string('dropoff_address')->nullable();

            $table->decimal('distance_km', 10, 3)->nullable();
            $table->unsignedBigInteger('fare_r')->nullable(); // fare in R (rupiah-style integer)
            $table->string('currency', 8)->default('R');
            $table->text('notes')->nullable();

            $table->timestamp('requested_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['driver_id', 'status']);
            $table->index('requested_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dd_trips');
    }
};
