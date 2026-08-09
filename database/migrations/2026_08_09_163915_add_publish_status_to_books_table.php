<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->string('status', 20)->default('published')->after('price');
            $table->timestamp('scheduled_at')->nullable()->after('status');
            $table->timestamp('published_at')->nullable()->after('scheduled_at');
        });

        // Existing books are already live
        DB::table('books')->whereNull('published_at')->update([
            'status' => 'published',
            'published_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)'),
        ]);

        Schema::table('books', function (Blueprint $table) {
            $table->index(['status', 'scheduled_at']);
            $table->index(['status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropIndex(['status', 'scheduled_at']);
            $table->dropIndex(['status', 'published_at']);
            $table->dropColumn(['status', 'scheduled_at', 'published_at']);
        });
    }
};
