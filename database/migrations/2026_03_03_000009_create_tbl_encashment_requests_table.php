<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_encashment_requests')) {
            return;
        }

        Schema::create('tbl_encashment_requests', function (Blueprint $table) {
            $table->bigIncrements('er_id');
            $table->string('er_reference_no', 40)->unique();
            $table->string('er_invoice_no', 40)->nullable()->unique();
            $table->unsignedBigInteger('er_customer_id');
            $table->decimal('er_amount', 12, 2);
            $table->string('er_channel', 20)->default('bank');
            $table->string('er_account_name', 255)->nullable();
            $table->string('er_account_number', 120)->nullable();
            $table->text('er_notes')->nullable();
            $table->string('er_status', 30)->default('pending');
            $table->text('er_admin_notes')->nullable();
            $table->text('er_accounting_notes')->nullable();
            $table->unsignedBigInteger('er_approved_by')->nullable();
            $table->timestamp('er_approved_at')->nullable();
            $table->unsignedBigInteger('er_released_by')->nullable();
            $table->timestamp('er_released_at')->nullable();
            $table->timestamps();

            $table->index(['er_customer_id', 'created_at'], 'er_customer_created_idx');
            $table->index('er_status', 'er_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_encashment_requests');
    }
};
