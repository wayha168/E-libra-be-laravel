<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // authors: 1 author profile per user
        Schema::table('authors', function (Blueprint $table) {
            if (!Schema::hasColumn('authors', 'user_id')) {
                $table->unsignedBigInteger('user_id')->unique()->after('id');
            }

            if (!Schema::hasColumn('authors', 'image_id')) {
                $table->unsignedBigInteger('image_id')->nullable()->after('user_id');
            }

            if (!Schema::hasColumn('authors', 'bio')) {
                $table->text('bio')->nullable()->after('image_id');
            }
        });

        Schema::table('authors', function (Blueprint $table) {
            if (Schema::hasColumn('authors', 'user_id') && !array_key_exists('user_id', $table->getColumns())) {
                // no-op (kept for safety; cannot detect here reliably)
            }
        });

        Schema::table('authors', function (Blueprint $table) {
            if (Schema::hasColumn('authors', 'user_id')) {
                // Add FKs with best-effort (may already exist in reruns)
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            }
            if (Schema::hasColumn('authors', 'image_id')) {
                $table->foreign('image_id')->references('id')->on('images')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            if (Schema::hasColumn('authors', 'image_id')) {
                $table->dropForeign(['image_id']);
                $table->dropColumn('image_id');
            }

            if (Schema::hasColumn('authors', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }

            if (Schema::hasColumn('authors', 'bio')) {
                $table->dropColumn('bio');
            }
        });
    }
};
