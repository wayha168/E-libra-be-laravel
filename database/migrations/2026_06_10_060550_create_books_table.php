<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Limited book fields + public availability date
            $table->string('title');
            $table->text('description')->nullable();
            $table->float('price')->nullable();

            // Genre is stored in category_id
            $table->uuid('author_id')->nullable();
            $table->uuid('category_id')->nullable();
            $table->uuid('image_id')->nullable();

            $table->date('public_date')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
