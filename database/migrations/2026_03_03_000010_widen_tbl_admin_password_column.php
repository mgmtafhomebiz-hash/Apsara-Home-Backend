<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbl_admin')) {
            return;
        }

        // Laravel password hashes (bcrypt/argon) are longer than 45 chars.
        DB::statement('ALTER TABLE tbl_admin ALTER COLUMN passworde TYPE VARCHAR(255)');
    }

    public function down(): void
    {
        if (!Schema::hasTable('tbl_admin')) {
            return;
        }

        DB::statement('ALTER TABLE tbl_admin ALTER COLUMN passworde TYPE VARCHAR(45)');
    }
};
