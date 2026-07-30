<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $this->migrateSqlite();

            return;
        }

        Schema::table('promotions', function (Blueprint $table) {
            $table->string('type', 32)->default('percentage')->after('id');
            $table->foreignUuid('author_id')->nullable()->after('book_id')->constrained('authors')->cascadeOnDelete();
            $table->unsignedSmallInteger('trial_days')->nullable()->after('discount_percent');
        });

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE promotions MODIFY book_id CHAR(36) NULL');
            DB::statement('ALTER TABLE promotions MODIFY discount_percent TINYINT UNSIGNED NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE promotions ALTER COLUMN book_id DROP NOT NULL');
            DB::statement('ALTER TABLE promotions ALTER COLUMN discount_percent DROP NOT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('promotions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('author_id');
            $table->dropColumn(['type', 'trial_days']);
        });
    }

    private function migrateSqlite(): void
    {
        if (Schema::hasColumn('promotions', 'type')) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        DB::statement('CREATE TABLE promotions_new (
            id CHAR(36) NOT NULL PRIMARY KEY,
            type VARCHAR(32) NOT NULL DEFAULT \'percentage\',
            book_id CHAR(36) NULL,
            author_id CHAR(36) NULL,
            created_by CHAR(36) NULL,
            discount_percent INTEGER NULL,
            trial_days INTEGER NULL,
            starts_at DATETIME NULL,
            ends_at DATETIME NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        )');

        DB::statement('INSERT INTO promotions_new
            (id, type, book_id, author_id, created_by, discount_percent, trial_days, starts_at, ends_at, is_active, created_at, updated_at)
            SELECT id, \'percentage\', book_id, NULL, created_by, discount_percent, NULL, starts_at, ends_at, is_active, created_at, updated_at
            FROM promotions');

        Schema::drop('promotions');
        DB::statement('ALTER TABLE promotions_new RENAME TO promotions');
        DB::statement('CREATE INDEX promotions_book_id_is_active_index ON promotions (book_id, is_active)');

        Schema::enableForeignKeyConstraints();
    }
};
