<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbl_encashment_requests')) {
            return;
        }

        Schema::table('tbl_encashment_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_encashment_requests', 'er_proof_url')) {
                $table->string('er_proof_url', 1200)->nullable()->after('er_accounting_notes');
            }
            if (!Schema::hasColumn('tbl_encashment_requests', 'er_proof_public_id')) {
                $table->string('er_proof_public_id', 255)->nullable()->after('er_proof_url');
            }
            if (!Schema::hasColumn('tbl_encashment_requests', 'er_proof_uploaded_by')) {
                $table->unsignedBigInteger('er_proof_uploaded_by')->nullable()->after('er_proof_public_id');
            }
            if (!Schema::hasColumn('tbl_encashment_requests', 'er_proof_uploaded_at')) {
                $table->timestamp('er_proof_uploaded_at')->nullable()->after('er_proof_uploaded_by');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tbl_encashment_requests')) {
            return;
        }

        Schema::table('tbl_encashment_requests', function (Blueprint $table) {
            $drop = [];
            foreach (['er_proof_uploaded_at', 'er_proof_uploaded_by', 'er_proof_public_id', 'er_proof_url'] as $column) {
                if (Schema::hasColumn('tbl_encashment_requests', $column)) {
                    $drop[] = $column;
                }
            }

            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
