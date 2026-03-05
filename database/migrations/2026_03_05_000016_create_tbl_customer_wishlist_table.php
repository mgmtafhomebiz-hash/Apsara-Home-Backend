<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_customer_wishlist')) {
            return;
        }

        Schema::create('tbl_customer_wishlist', function (Blueprint $table) {
            $table->bigIncrements('cw_id');
            $table->unsignedBigInteger('cw_customer_id');
            $table->unsignedBigInteger('cw_product_id');
            $table->timestamp('cw_date')->nullable();

            $table->unique(['cw_customer_id', 'cw_product_id'], 'uniq_customer_product_wishlist');
            $table->index('cw_customer_id', 'idx_cw_customer_id');
            $table->index('cw_product_id', 'idx_cw_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_customer_wishlist');
    }
};
