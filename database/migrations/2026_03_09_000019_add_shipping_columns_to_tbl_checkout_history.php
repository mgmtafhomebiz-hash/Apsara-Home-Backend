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
            if (!Schema::hasColumn('tbl_checkout_history', 'ch_courier')) {
                $table->string('ch_courier', 40)->nullable()->after('ch_fulfillment_status');
            }
            if (!Schema::hasColumn('tbl_checkout_history', 'ch_tracking_no')) {
                $table->string('ch_tracking_no', 120)->nullable()->after('ch_courier');
            }
            if (!Schema::hasColumn('tbl_checkout_history', 'ch_shipment_status')) {
                $table->string('ch_shipment_status', 50)->nullable()->after('ch_tracking_no');
            }
            if (!Schema::hasColumn('tbl_checkout_history', 'ch_shipment_payload')) {
                $table->json('ch_shipment_payload')->nullable()->after('ch_shipment_status');
            }
            if (!Schema::hasColumn('tbl_checkout_history', 'ch_shipped_at')) {
                $table->timestamp('ch_shipped_at')->nullable()->after('ch_shipment_payload');
            }
        });

        Schema::table('tbl_checkout_history', function (Blueprint $table) {
            $table->index('ch_courier', 'ch_courier_idx');
            $table->index('ch_tracking_no', 'ch_tracking_no_idx');
            $table->index('ch_shipment_status', 'ch_shipment_status_idx');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tbl_checkout_history')) {
            return;
        }

        Schema::table('tbl_checkout_history', function (Blueprint $table) {
            foreach (['ch_courier_idx', 'ch_tracking_no_idx', 'ch_shipment_status_idx'] as $index) {
                try {
                    $table->dropIndex($index);
                } catch (\Throwable) {
                    // Ignore missing index during rollback safety.
                }
            }
        });

        Schema::table('tbl_checkout_history', function (Blueprint $table) {
            foreach (['ch_shipped_at', 'ch_shipment_payload', 'ch_shipment_status', 'ch_tracking_no', 'ch_courier'] as $column) {
                if (Schema::hasColumn('tbl_checkout_history', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

