<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_admin') && !Schema::hasColumn('tbl_admin', 'supplier_id')) {
            Schema::table('tbl_admin', function (Blueprint $table) {
                $table->unsignedBigInteger('supplier_id')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tbl_admin') && Schema::hasColumn('tbl_admin', 'supplier_id')) {
            Schema::table('tbl_admin', function (Blueprint $table) {
                $table->dropColumn('supplier_id');
            });
        }
    }
};
