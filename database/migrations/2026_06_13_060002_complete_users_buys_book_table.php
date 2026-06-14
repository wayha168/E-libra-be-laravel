<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rebuild schema safely by creating table only if missing.
        // Your existing migration file creates nothing (empty up()), so this will create the table.

        if (!Schema::hasTable('users_buys_book')) {
            Schema::create('users_buys_book', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('user_id');
                $table->uuid('book_id');

                $table->float('amount')->nullable();
                $table->string('status')->default('paid'); // paid | pending | canceled

                $table->dateTime('purchased_at')->nullable();
                $table->dateTime('expires_at')->nullable();

                $table->timestamps();

                $table->unique(['user_id', 'book_id'], 'user_buy_book_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('users_buys_book');
    }
};
