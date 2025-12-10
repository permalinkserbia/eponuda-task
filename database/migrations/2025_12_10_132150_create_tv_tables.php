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
        // Create TV categories table
        Schema::create('tv_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('url');
            $table->foreignId('parent_id')->nullable()->constrained('tv_categories')->onDelete('cascade');
            $table->timestamps();
        });

        // Create televisions table
        Schema::create('televisions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 10, 2)->nullable();
            $table->string('image')->nullable();
            $table->string('product_link')->nullable();
            $table->text('specs')->nullable();
            $table->foreignId('tv_category_id')->nullable()->constrained('tv_categories')->onDelete('set null');
            $table->string('external_id')->nullable()->unique();
            $table->timestamps();

            // Add indexes for performance
            $table->index('tv_category_id');
            $table->index('created_at');
            $table->index('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('televisions');
        Schema::dropIfExists('tv_categories');
    }
};
