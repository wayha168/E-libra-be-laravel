<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users_buys_book', function (Blueprint $table) {
            if (!Schema::hasColumn('users_buys_book', 'stripe_checkout_session_id')) {
                $table->string('stripe_checkout_session_id')->nullable()->after('status');
            }
            if (!Schema::hasColumn('users_buys_book', 'stripe_payment_intent_id')) {
                $table->string('stripe_payment_intent_id')->nullable()->after('stripe_checkout_session_id');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'stripe_customer_id')) {
                $table->string('stripe_customer_id')->nullable()->after('user_subscribe');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users_buys_book', function (Blueprint $table) {
            $table->dropColumn(['stripe_checkout_session_id', 'stripe_payment_intent_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('stripe_customer_id');
        });
    }
};
