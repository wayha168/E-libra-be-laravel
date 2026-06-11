<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            if (!Schema::hasColumn('authors', 'user_id')) {
                $table->foreignUuid('user_id')->unique()->after('id')->constrained('users')->cascadeOnDelete();
            }

            if (!Schema::hasColumn('authors', 'image_id')) {
                $table->foreignUuid('image_id')->nullable()->after('user_id')->constrained('images')->nullOnDelete();
            }

            if (!Schema::hasColumn('authors', 'bio')) {
                $table->text('bio')->nullable()->after('image_id');
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
