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
            // Drop FKs using column existence checks.
            // Some environments may have FK names that don't match Laravel's default;
            // this approach avoids failing hard during rollback.
            // Some rollbacks can fail due to FK-name mismatches; attempt to drop by FK constraint name too.
            if (Schema::hasColumn('books', 'image_id')) {
                try {
                    $table->dropForeign(['image_id']);
                } catch (\Throwable $e) {
                    // ignore
                }

                // Best-effort: drop known default FK name if present
                try {
                    $table->dropForeign('books_image_id_foreign');
                } catch (\Throwable $e) {
                    // ignore
                }

                $table->dropColumn('image_id');
            }


            if (Schema::hasColumn('books', 'category_id')) {
                try {
                    $table->dropForeign(['category_id']);
                } catch (\Throwable $e) {
                    // ignore
                }
                $table->dropColumn('category_id');
            }

            if (Schema::hasColumn('books', 'author_id')) {
                try {
                    $table->dropForeign(['author_id']);
                } catch (\Throwable $e) {
                    // ignore
                }
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
