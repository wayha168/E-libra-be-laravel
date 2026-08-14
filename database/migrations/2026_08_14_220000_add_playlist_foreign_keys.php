<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('playlists', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });

        Schema::table('playlist_books', function (Blueprint $table) {
            $table->foreign('playlist_id')
                ->references('id')
                ->on('playlists')
                ->cascadeOnDelete();

            $table->foreign('book_id')
                ->references('id')
                ->on('books')
                ->cascadeOnDelete();
        });

        Schema::table('playlist_likes', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('playlist_id')
                ->references('id')
                ->on('playlists')
                ->cascadeOnDelete();
        });

        Schema::table('playlist_comments', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('playlist_id')
                ->references('id')
                ->on('playlists')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('playlist_comments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['playlist_id']);
        });

        Schema::table('playlist_likes', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['playlist_id']);
        });

        Schema::table('playlist_books', function (Blueprint $table) {
            $table->dropForeign(['playlist_id']);
            $table->dropForeign(['book_id']);
        });

        Schema::table('playlists', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
    }
};
