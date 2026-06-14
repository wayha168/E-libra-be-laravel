<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'payway_account')) {
                $table->string('payway_account')->nullable();
            }
            if (!Schema::hasColumn('users', 'bakong_account')) {
                $table->string('bakong_account')->nullable();
            }
        });

        Schema::table('users_buys_book', function (Blueprint $table) {
            if (!Schema::hasColumn('users_buys_book', 'admin_commission_rate')) {
                $table->float('admin_commission_rate')->nullable()->after('amount');
            }
            if (!Schema::hasColumn('users_buys_book', 'admin_commission_amount')) {
                $table->float('admin_commission_amount')->nullable()->after('admin_commission_rate');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['payway_account', 'bakong_account']);
        });

        Schema::table('users_buys_book', function (Blueprint $table) {
            $table->dropColumn(['admin_commission_rate', 'admin_commission_amount']);
        });
    }
};
