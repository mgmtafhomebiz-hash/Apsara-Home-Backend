<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbl_supplier_user')) {
            return;
        }

        DB::statement('ALTER TABLE tbl_supplier_user ALTER COLUMN su_password TYPE VARCHAR(255)');
        DB::statement('ALTER TABLE tbl_supplier_user ALTER COLUMN su_email TYPE VARCHAR(255)');
    }

    public function down(): void
    {
        if (!Schema::hasTable('tbl_supplier_user')) {
            return;
        }

        DB::statement('ALTER TABLE tbl_supplier_user ALTER COLUMN su_password TYPE VARCHAR(45)');
        DB::statement('ALTER TABLE tbl_supplier_user ALTER COLUMN su_email TYPE VARCHAR(45)');
    }
};
