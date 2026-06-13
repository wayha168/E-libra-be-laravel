<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_category_permissions', function (Blueprint $table) {
            // Using uuid for ids to match existing User/Category/Permission models



            $table->uuid('id')->primary();

            $table->uuid('user_id');
            $table->uuid('category_id');
            $table->uuid('permission_id');

            $table->timestamps();

            $table->unique(['user_id', 'category_id', 'permission_id'], 'user_category_permission_unique');

            // FK constraints are optional; add them if your DB is already enforcing
            // $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            // $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete();
            // $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_category_permissions');
    }
};

