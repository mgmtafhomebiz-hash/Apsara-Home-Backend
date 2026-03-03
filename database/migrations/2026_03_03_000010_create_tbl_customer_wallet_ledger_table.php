<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_customer_wallet_ledger')) {
            return;
        }

        Schema::create('tbl_customer_wallet_ledger', function (Blueprint $table) {
            $table->bigIncrements('wl_id');
            $table->unsignedBigInteger('wl_customer_id');
            $table->string('wl_wallet_type', 20); // cash | pv
            $table->string('wl_entry_type', 20); // credit | debit
            $table->decimal('wl_amount', 12, 2);
            $table->string('wl_source_type', 40)->nullable(); // order | encashment | manual
            $table->unsignedBigInteger('wl_source_id')->nullable();
            $table->string('wl_reference_no', 120)->nullable();
            $table->text('wl_notes')->nullable();
            $table->unsignedBigInteger('wl_created_by')->nullable(); // admin id if applicable
            $table->timestamps();

            $table->index(['wl_customer_id', 'wl_wallet_type'], 'wl_customer_wallet_idx');
            $table->index(['wl_source_type', 'wl_source_id'], 'wl_source_idx');
            $table->unique(['wl_wallet_type', 'wl_entry_type', 'wl_source_type', 'wl_source_id'], 'wl_source_entry_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_customer_wallet_ledger');
    }
};

