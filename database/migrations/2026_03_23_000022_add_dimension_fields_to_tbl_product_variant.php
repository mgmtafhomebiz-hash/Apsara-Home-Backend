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

        Schema::table('tbl_product_variant', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_product_variant', 'pv_width')) {
                $table->decimal('pv_width', 12, 2)->nullable()->after('pv_size');
            }

            if (!Schema::hasColumn('tbl_product_variant', 'pv_dimension')) {
                $table->decimal('pv_dimension', 12, 2)->nullable()->after('pv_width');
            }

            if (!Schema::hasColumn('tbl_product_variant', 'pv_height')) {
                $table->decimal('pv_height', 12, 2)->nullable()->after('pv_dimension');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tbl_product_variant')) {
            return;
        }

        Schema::table('tbl_product_variant', function (Blueprint $table) {
            foreach (['pv_height', 'pv_dimension', 'pv_width'] as $column) {
                if (Schema::hasColumn('tbl_product_variant', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
