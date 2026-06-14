<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('book_id')->constrained('books')->cascadeOnDelete();
            $table->foreignUuid('image_id')->constrained('images')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['book_id', 'image_id']);
        });

        if (Schema::hasColumn('books', 'image_id')) {
            $rows = DB::table('books')->whereNotNull('image_id')->get(['id', 'image_id']);
            foreach ($rows as $row) {
                DB::table('book_images')->insert([
                    'id' => (string) Str::uuid(),
                    'book_id' => $row->id,
                    'image_id' => $row->image_id,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('book_images');
    }
};
