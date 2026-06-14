<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users_buys_book', function (Blueprint $table) {
            if (!Schema::hasColumn('users_buys_book', 'payment_method')) {
                $table->string('payment_method')->default('card')->after('amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users_buys_book', function (Blueprint $table) {
            if (Schema::hasColumn('users_buys_book', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
        });
    }
};
