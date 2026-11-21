<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            // restrictOnDelete : une catégorie contenant des produits ne peut pas être supprimée.
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name', 180);
            $table->string('slug', 200)->unique();
            $table->string('sku', 64)->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);                 // prix de base en MAD
            $table->decimal('sale_price', 10, 2)->nullable(); // prix remisé (optionnel, < price)
            $table->json('sizes')->nullable();   // ["S","M","L"]
            $table->json('colors')->nullable();  // [{"name":"Noir","hex":"#111111"}]
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'created_at']);
            $table->index(['is_active', 'is_featured']);
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path'); // chemin relatif au disque « public »
            $table->string('alt')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
    }
};
