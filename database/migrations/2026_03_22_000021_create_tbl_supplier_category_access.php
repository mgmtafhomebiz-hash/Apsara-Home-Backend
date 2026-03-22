<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_supplier_category_access')) {
            return;
        }

        Schema::create('tbl_supplier_category_access', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('category_id');
            $table->timestamp('created_at')->nullable();

            $table->unique(['supplier_id', 'category_id'], 'supplier_category_access_unique');
            $table->index('supplier_id');
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_supplier_category_access');
    }
};
