<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dd_driver_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained('users')->cascadeOnDelete();

            // Licence details (manual and/or from scan)
            $table->string('licence_number')->nullable();
            $table->string('licence_class')->nullable();
            $table->string('licence_country')->nullable();
            $table->date('licence_issued_at')->nullable();
            $table->date('licence_expires_at')->nullable();
            $table->string('licence_scan_path')->nullable();
            $table->json('licence_ocr_stub')->nullable();

            // Address
            $table->string('address_line')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();
            $table->decimal('address_latitude', 10, 7)->nullable();
            $table->decimal('address_longitude', 10, 7)->nullable();

            // Live location / availability for nearby matching
            $table->decimal('current_latitude', 10, 7)->nullable();
            $table->decimal('current_longitude', 10, 7)->nullable();
            $table->timestamp('location_updated_at')->nullable();
            $table->boolean('is_available')->default(false);
            $table->string('vehicle_type')->nullable();
            $table->string('vehicle_plate')->nullable();
            $table->string('status')->default('pending'); // pending|approved|rejected|suspended

            $table->timestamps();

            $table->index(['is_available', 'status']);
            $table->index(['current_latitude', 'current_longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dd_driver_profiles');
    }
};
