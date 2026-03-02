<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbl_product')) {
            return;
        }

        if (!Schema::hasTable('tbl_product_photo')) {
            Schema::create('tbl_product_photo', function (Blueprint $table) {
                $table->bigIncrements('pp_id');
                $table->unsignedBigInteger('pp_pdid');
                $table->string('pp_filename', 1000);
                $table->string('pp_varone', 80)->nullable();
                $table->timestamp('pp_date')->nullable();

                $table->index('pp_pdid');
            });
        }

        if (!Schema::hasTable('tbl_product_variant')) {
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
            });
        }

        if (!Schema::hasTable('tbl_product_variant_photo')) {
            Schema::create('tbl_product_variant_photo', function (Blueprint $table) {
                $table->bigIncrements('pvp_id');
                $table->unsignedBigInteger('pvp_pvid');
                $table->string('pvp_filename', 1000);
                $table->integer('pvp_sort')->default(0);
                $table->timestamp('pvp_date')->nullable();

                $table->index('pvp_pvid');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tbl_product_variant_photo')) {
            Schema::drop('tbl_product_variant_photo');
        }
        if (Schema::hasTable('tbl_product_variant')) {
            Schema::drop('tbl_product_variant');
        }
        if (Schema::hasTable('tbl_product_photo')) {
            Schema::drop('tbl_product_photo');
        }
    }
};
