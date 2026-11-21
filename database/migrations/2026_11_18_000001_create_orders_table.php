<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Commandes passées sur la boutique (plus de redirection WhatsApp).
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->nullable()->unique(); // YS-000123, attribuée après insertion
            $table->string('status', 20)->default('nouvelle')->index();

            $table->string('customer_name', 120);
            $table->string('customer_phone', 20);
            $table->string('customer_city', 80);
            $table->string('customer_address', 255);

            $table->unsignedInteger('items_count');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('delivery_fee', 10, 2)->nullable(); // saisi par l'admin après confirmation
            $table->text('admin_note')->nullable();

            $table->timestamps();
            $table->index('created_at');
        });

        // Lignes de commande : copie figée du produit au moment de l'achat.
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name', 160);
            $table->string('sku', 60);
            $table->string('size', 30)->nullable();
            $table->string('color', 60)->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->unsignedSmallInteger('quantity');
            $table->decimal('line_total', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
