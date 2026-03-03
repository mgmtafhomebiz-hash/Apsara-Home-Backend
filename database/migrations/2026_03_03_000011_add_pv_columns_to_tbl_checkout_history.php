<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbl_checkout_history')) {
            return;
        }

        Schema::table('tbl_checkout_history', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_checkout_history', 'ch_product_id')) {
                $table->unsignedBigInteger('ch_product_id')->nullable()->after('ch_product_name');
            }

            if (!Schema::hasColumn('tbl_checkout_history', 'ch_product_sku')) {
                $table->string('ch_product_sku', 100)->nullable()->after('ch_product_id');
            }

            if (!Schema::hasColumn('tbl_checkout_history', 'ch_product_pv')) {
                $table->decimal('ch_product_pv', 12, 2)->default(0)->after('ch_product_sku');
            }

            if (!Schema::hasColumn('tbl_checkout_history', 'ch_earned_pv')) {
                $table->decimal('ch_earned_pv', 12, 2)->default(0)->after('ch_product_pv');
            }

            if (!Schema::hasColumn('tbl_checkout_history', 'ch_pv_posted_at')) {
                $table->timestamp('ch_pv_posted_at')->nullable()->after('ch_earned_pv');
            }
        });

        Schema::table('tbl_checkout_history', function (Blueprint $table) {
            $table->index('ch_product_id', 'ch_product_id_idx');
            $table->index('ch_pv_posted_at', 'ch_pv_posted_at_idx');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tbl_checkout_history')) {
            return;
        }

        Schema::table('tbl_checkout_history', function (Blueprint $table) {
            foreach ([
                'ch_product_id_idx',
                'ch_pv_posted_at_idx',
            ] as $index) {
                try {
                    $table->dropIndex($index);
                } catch (\Throwable) {
                }
            }

            foreach ([
                'ch_pv_posted_at',
                'ch_earned_pv',
                'ch_product_pv',
                'ch_product_sku',
                'ch_product_id',
            ] as $column) {
                if (Schema::hasColumn('tbl_checkout_history', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

