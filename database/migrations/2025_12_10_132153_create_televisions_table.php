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
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('televisions');
    }
};
