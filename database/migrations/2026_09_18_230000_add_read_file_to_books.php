<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            // Compact, DB-stored copy of the book PDF for offline reading.
            // Kept small via a configurable size cap (config: elibra.book_read_max_kb).
            if (! Schema::hasColumn('books', 'pdf_read_data')) {
                $table->longText('pdf_read_data')->nullable()->after('pdf_preview_path');
            }
            if (! Schema::hasColumn('books', 'pdf_read_size')) {
                $table->unsignedBigInteger('pdf_read_size')->nullable()->after('pdf_read_data');
            }
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            foreach (['pdf_read_size', 'pdf_read_data'] as $column) {
                if (Schema::hasColumn('books', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
