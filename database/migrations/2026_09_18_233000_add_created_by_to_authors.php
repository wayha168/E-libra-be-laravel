<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            // The staff/admin user who created this author record (distinct from
            // authors.user_id, which is the author's own login account).
            if (! Schema::hasColumn('authors', 'created_by')) {
                $table->foreignUuid('created_by')->nullable()->after('user_id')
                    ->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            if (Schema::hasColumn('authors', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
        });
    }
};
