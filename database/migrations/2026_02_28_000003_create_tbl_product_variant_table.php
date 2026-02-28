<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_product_variant', function (Blueprint $table) {
            $table->bigIncrements('pv_id');
            $table->unsignedBigInteger('pv_pdid');
            $table->string('pv_sku', 80)->nullable();
            $table->string('pv_color', 80)->nullable();
            $table->string('pv_color_hex', 16)->nullable();
            $table->string('pv_size', 40)->nullable();
            $table->decimal('pv_price_srp', 12, 2)->nullable();
            $table->decimal('pv_price_dp', 12, 2)->nullable();
            $table->decimal('pv_qty', 12, 2)->nullable();
            $table->smallInteger('pv_status')->default(1);
            $table->timestamp('pv_date')->nullable();

            $table->index('pv_pdid');
            $table->foreign('pv_pdid')
                ->references('pd_id')
                ->on('tbl_product')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_product_variant');
    }
};

