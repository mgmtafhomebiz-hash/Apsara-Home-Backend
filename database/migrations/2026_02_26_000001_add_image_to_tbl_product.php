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

        if (Schema::hasColumn('tbl_product', 'pd_image')) {
            return;
        }

        Schema::table('tbl_product', function (Blueprint $table) {
            $table->string('pd_image', 500)->nullable()->default(null)->after('pd_video');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tbl_product') || !Schema::hasColumn('tbl_product', 'pd_image')) {
            return;
        }

        Schema::table('tbl_product', function (Blueprint $table) {
            $table->dropColumn('pd_image');
        });
    }
};
