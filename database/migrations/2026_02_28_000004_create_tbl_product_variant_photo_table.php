<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbl_product_variant')) {
            return;
        }

        if (Schema::hasTable('tbl_product_variant_photo')) {
            return;
        }

        Schema::create('tbl_product_variant_photo', function (Blueprint $table) {
            $table->bigIncrements('pvp_id');
            $table->unsignedBigInteger('pvp_pvid');
            $table->string('pvp_filename', 1000);
            $table->integer('pvp_sort')->default(0);
            $table->timestamp('pvp_date')->nullable();

            $table->index('pvp_pvid');
            $table->foreign('pvp_pvid')
                ->references('pv_id')
                ->on('tbl_product_variant')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tbl_product_variant_photo')) {
            return;
        }

        Schema::dropIfExists('tbl_product_variant_photo');
    }
};
