<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            if (!Schema::hasColumn('books', 'pdf_file')) {
                $table->string('pdf_file')->nullable()->after('price');
            }
            if (!Schema::hasColumn('books', 'pdf_preview_path')) {
                $table->string('pdf_preview_path')->nullable()->after('pdf_file');
            }
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            if (Schema::hasColumn('books', 'pdf_preview_path')) {
                $table->dropColumn('pdf_preview_path');
            }
        });
    }
};
