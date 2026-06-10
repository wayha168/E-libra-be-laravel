<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'status')) {
                $table->string('status')->default('active')->after('confirm_password');
            }

            if (!Schema::hasColumn('users', 'profile_image_id')) {
                $table->unsignedBigInteger('profile_image_id')->nullable()->after('status');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'profile_image_id')) {
                $table->foreign('profile_image_id')
                    ->references('id')
                    ->on('images')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'profile_image_id')) {
                $table->dropForeign(['profile_image_id']);
                $table->dropColumn('profile_image_id');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
