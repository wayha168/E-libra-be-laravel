<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aba_payway_merchants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('merchant_id', 30);
            $table->text('api_key');
            $table->string('merchant_name')->nullable();
            $table->string('environment', 20)->default('sandbox');
            $table->string('currency', 3)->default('USD');
            $table->string('payment_option', 40)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aba_payway_merchants');
    }
};
