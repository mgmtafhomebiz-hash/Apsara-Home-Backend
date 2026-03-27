<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_product_activity_logs', function (Blueprint $table) {
            $table->bigIncrements('pal_id');
            $table->unsignedBigInteger('pal_product_id')->nullable();
            $table->unsignedBigInteger('pal_supplier_id')->nullable();
            $table->unsignedBigInteger('pal_admin_id')->nullable();
            $table->unsignedBigInteger('pal_supplier_user_id')->nullable();
            $table->string('pal_action', 32);
            $table->string('pal_status', 16)->default('success');
            $table->string('pal_product_name', 255);
            $table->string('pal_product_sku', 80)->nullable();
            $table->string('pal_actor_name', 255)->nullable();
            $table->string('pal_actor_email', 255)->nullable();
            $table->string('pal_actor_role', 80)->nullable();
            $table->timestamp('pal_created_at')->useCurrent();

            $table->index(['pal_created_at']);
            $table->index(['pal_product_id']);
            $table->index(['pal_admin_id']);
            $table->index(['pal_supplier_user_id']);
            $table->index(['pal_supplier_id']);
            $table->index(['pal_action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_product_activity_logs');
    }
};
