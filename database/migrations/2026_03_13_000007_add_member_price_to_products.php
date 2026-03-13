<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_product') && !Schema::hasColumn('tbl_product', 'pd_price_member')) {
            Schema::table('tbl_product', function (Blueprint $table) {
                $table->decimal('pd_price_member', 12, 2)->nullable();
            });
        }

        if (Schema::hasTable('tbl_product_variant') && !Schema::hasColumn('tbl_product_variant', 'pv_price_member')) {
            Schema::table('tbl_product_variant', function (Blueprint $table) {
                $table->decimal('pv_price_member', 12, 2)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tbl_product_variant') && Schema::hasColumn('tbl_product_variant', 'pv_price_member')) {
            Schema::table('tbl_product_variant', function (Blueprint $table) {
                $table->dropColumn('pv_price_member');
            });
        }

        if (Schema::hasTable('tbl_product') && Schema::hasColumn('tbl_product', 'pd_price_member')) {
            Schema::table('tbl_product', function (Blueprint $table) {
                $table->dropColumn('pd_price_member');
            });
        }
    }
};
