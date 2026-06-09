<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add role_id column (if not exists)
        Schema::table('users', function (Blueprint $table) {
            // role_id is required for role relationship
            if (!Schema::hasColumn('users', 'role_id')) {
                $table->unsignedBigInteger('role_id')->nullable(false)->after('email');
            }
        });

        // Add foreign key (keep existing primary key as-is to avoid MySQL auto-column errors)
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('role_id')
                ->references('id')
                ->on('user_roles')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role_id')) {
                $table->dropForeign(['role_id']);
                $table->dropColumn('role_id');
            }

            // No primary-key changes on rollback.
            // The initial users table uses the default $table->id() primary key.
            // role_id/email should not define a composite primary key (MySQL would reject it).

            // (Intentionally left blank)
        });
    }
};
