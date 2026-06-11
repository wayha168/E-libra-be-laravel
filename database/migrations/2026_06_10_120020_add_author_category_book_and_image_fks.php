<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'image_id')) {
                $table->foreignUuid('image_id')->nullable()->after('parent_id')->constrained('images')->nullOnDelete();
            }
            if (!Schema::hasColumn('categories', 'banner_image_id')) {
                $table->foreignUuid('banner_image_id')->nullable()->after('image_id')->constrained('images')->nullOnDelete();
            }
        });

        Schema::table('books', function (Blueprint $table) {
            if (!Schema::hasColumn('books', 'author_id')) {
                $table->foreignUuid('author_id')->after('id')->constrained('authors')->cascadeOnDelete();
            }

            if (!Schema::hasColumn('books', 'category_id')) {
                $table->foreignUuid('category_id')->after('author_id')->constrained('categories')->cascadeOnDelete();
            }

            if (!Schema::hasColumn('books', 'image_id')) {
                $table->foreignUuid('image_id')->nullable()->after('category_id')->constrained('images')->nullOnDelete();
            }

            if (!Schema::hasColumn('books', 'title')) {
                $table->string('title')->after('image_id');
            }

            if (!Schema::hasColumn('books', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            if (Schema::hasColumn('books', 'image_id')) {
                $table->dropForeign(['image_id']);
                $table->dropColumn('image_id');
            }
            if (Schema::hasColumn('books', 'category_id')) {
                $table->dropForeign(['category_id']);
                $table->dropColumn('category_id');
            }
            if (Schema::hasColumn('books', 'author_id')) {
                $table->dropForeign(['author_id']);
                $table->dropColumn('author_id');
            }

            if (Schema::hasColumn('books', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('books', 'title')) {
                $table->dropColumn('title');
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'banner_image_id')) {
                $table->dropForeign(['banner_image_id']);
                $table->dropColumn('banner_image_id');
            }
            if (Schema::hasColumn('categories', 'image_id')) {
                $table->dropForeign(['image_id']);
                $table->dropColumn('image_id');
            }
        });
    }
};
