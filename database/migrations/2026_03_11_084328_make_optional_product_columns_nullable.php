<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $columns = [
        'pd_image',
        'pd_specifications',
        'pd_material',
        'pd_description',
        'pd_parent_sku',
        'pd_preorder',
    ];

    public function up(): void
    {
        foreach ($this->columns as $column) {
            if (Schema::hasColumn('tbl_product', $column)) {
                DB::statement("ALTER TABLE tbl_product ALTER COLUMN {$column} DROP NOT NULL");
            }
        }
    }

    public function down(): void
    {
        // Intentionally left blank — reverting NOT NULL is destructive
    }
};
