<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_product_photo', function (Blueprint $table) {
            $table->bigIncrements('pp_id');
            $table->unsignedBigInteger('pp_pdid');
            $table->string('pp_filename', 1000);
            $table->string('pp_varone', 80)->nullable();
            $table->timestamp('pp_date')->nullable();

            $table->index('pp_pdid');
            $table->foreign('pp_pdid')
                ->references('pd_id')
                ->on('tbl_product')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_product_photo');
    }
};

