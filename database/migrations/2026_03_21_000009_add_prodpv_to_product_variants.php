<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_product_variant') && !Schema::hasColumn('tbl_product_variant', 'pv_prodpv')) {
            Schema::table('tbl_product_variant', function (Blueprint $table) {
                $table->decimal('pv_prodpv', 12, 2)->nullable()->after('pv_price_member');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tbl_product_variant') && Schema::hasColumn('tbl_product_variant', 'pv_prodpv')) {
            Schema::table('tbl_product_variant', function (Blueprint $table) {
                $table->dropColumn('pv_prodpv');
            });
        }
    }
};
