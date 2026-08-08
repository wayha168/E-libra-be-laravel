<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aba_payway_merchants', function (Blueprint $table) {
            if (! Schema::hasColumn('aba_payway_merchants', 'is_platform')) {
                $table->boolean('is_platform')->default(false)->after('is_active');
                $table->index('is_platform');
            }
        });
    }

    public function down(): void
    {
        Schema::table('aba_payway_merchants', function (Blueprint $table) {
            if (Schema::hasColumn('aba_payway_merchants', 'is_platform')) {
                $table->dropIndex(['is_platform']);
                $table->dropColumn('is_platform');
            }
        });
    }
};
