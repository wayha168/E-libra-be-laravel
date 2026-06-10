<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // categories: connect to images (image + banner)
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'image_id')) {
                $table->unsignedBigInteger('image_id')->nullable()->after('parent_id');
            }
            if (!Schema::hasColumn('categories', 'banner_image_id')) {
                $table->unsignedBigInteger('banner_image_id')->nullable()->after('image_id');
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'image_id')) {
                $table->foreign('image_id')->references('id')->on('images')->nullOnDelete();
            }
            if (Schema::hasColumn('categories', 'banner_image_id')) {
                $table->foreign('banner_image_id')->references('id')->on('images')->nullOnDelete();
            }
        });

        // books: connect to author/category + book image
        Schema::table('books', function (Blueprint $table) {
            if (!Schema::hasColumn('books', 'author_id')) {
                $table->unsignedBigInteger('author_id')->after('id');
            }

            if (!Schema::hasColumn('books', 'category_id')) {
                $table->unsignedBigInteger('category_id')->after('author_id');
            }

            if (!Schema::hasColumn('books', 'image_id')) {
                $table->unsignedBigInteger('image_id')->nullable()->after('category_id');
            }

            if (!Schema::hasColumn('books', 'title')) {
                $table->string('title')->after('image_id');
            }

            if (!Schema::hasColumn('books', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
        });

        Schema::table('books', function (Blueprint $table) {
            if (Schema::hasColumn('books', 'author_id')) {
                $table->foreign('author_id')->references('id')->on('authors')->cascadeOnDelete();
            }
            if (Schema::hasColumn('books', 'category_id')) {
                $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete();
            }
            if (Schema::hasColumn('books', 'image_id')) {
                $table->foreign('image_id')->references('id')->on('images')->nullOnDelete();
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
