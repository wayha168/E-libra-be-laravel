<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users_buys_book', function (Blueprint $table) {
            if (! Schema::hasColumn('users_buys_book', 'payway_tran_id')) {
                $table->string('payway_tran_id', 40)->nullable()->after('stripe_payment_intent_id');
                $table->index('payway_tran_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users_buys_book', function (Blueprint $table) {
            if (Schema::hasColumn('users_buys_book', 'payway_tran_id')) {
                $table->dropIndex(['payway_tran_id']);
                $table->dropColumn('payway_tran_id');
            }
        });
    }
};
