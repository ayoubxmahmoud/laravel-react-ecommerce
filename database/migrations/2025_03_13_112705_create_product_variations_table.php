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
        // Table to define a diffrent variation type for a product (e.g Size, Color)
        Schema::create('variation_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id') // Links the variation type to a specific product
                ->index()
                ->constrained('products')
                ->cascadeOnDelete();
            $table->string('name'); // Name of the variation; e.g: 'Size', 'Color'
            $table->string('type'); // e.g: 'dropdown', 'radio'
        });
        // Table to store options for each variation type (e.g: Small,Medium for 'Side')
        Schema::create('variation_type_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variation_type_id') // Links option to a specific variation type
                ->index()
                ->constrained('variation_types')
                ->cascadeOnDelete();
            $table->string('name');
        });
        // Table to manage variations of a product (e.g: 'Red','Small' with price)
        Schema::create('product_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id') // Links variation to a product
                ->index()
                ->constrained('products')
                ->cascadeOnDelete();
            $table->json('variation_type_option_ids'); // JSON storing selected variation type option IDs
            $table->integer('quantity')->nullable();
            $table->decimal('price', 20, 4)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variations');
    }
};
