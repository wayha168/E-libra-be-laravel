<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            if (! Schema::hasColumn('authors', 'website')) {
                $table->string('website')->nullable()->after('bio');
            }
            if (! Schema::hasColumn('authors', 'facebook')) {
                $table->string('facebook')->nullable()->after('website');
            }
            if (! Schema::hasColumn('authors', 'instagram')) {
                $table->string('instagram')->nullable()->after('facebook');
            }
            if (! Schema::hasColumn('authors', 'twitter')) {
                $table->string('twitter')->nullable()->after('instagram');
            }
            if (! Schema::hasColumn('authors', 'tiktok')) {
                $table->string('tiktok')->nullable()->after('twitter');
            }
            if (! Schema::hasColumn('authors', 'youtube')) {
                $table->string('youtube')->nullable()->after('tiktok');
            }
            if (! Schema::hasColumn('authors', 'telegram')) {
                $table->string('telegram')->nullable()->after('youtube');
            }
        });
    }

    public function down(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            foreach (['website', 'facebook', 'instagram', 'twitter', 'tiktok', 'youtube', 'telegram'] as $column) {
                if (Schema::hasColumn('authors', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
