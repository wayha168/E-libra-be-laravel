<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('playlists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(true);
            $table->unsignedBigInteger('views_count')->default(0);
            $table->timestamps();

            $table->index('user_id');
            $table->index('is_public');
        });

        Schema::create('playlist_books', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('playlist_id');
            $table->uuid('book_id');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['playlist_id', 'book_id']);
            $table->index('book_id');
        });

        Schema::create('playlist_likes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('playlist_id');
            $table->timestamps();

            $table->unique(['user_id', 'playlist_id']);
        });

        Schema::create('playlist_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('playlist_id');
            $table->text('body');
            $table->timestamps();

            $table->index('playlist_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playlist_comments');
        Schema::dropIfExists('playlist_likes');
        Schema::dropIfExists('playlist_books');
        Schema::dropIfExists('playlists');
    }
};
