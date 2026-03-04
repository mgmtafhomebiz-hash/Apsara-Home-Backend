<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_customer_verification_requests')) {
            return;
        }

        Schema::create('tbl_customer_verification_requests', function (Blueprint $table) {
            $table->bigIncrements('cvr_id');
            $table->unsignedBigInteger('cvr_customer_id');
            $table->string('cvr_reference_no', 40)->unique();
            $table->string('cvr_status', 30)->default('pending_review');
            $table->string('cvr_full_name', 255);
            $table->date('cvr_birth_date')->nullable();
            $table->string('cvr_id_type', 60);
            $table->string('cvr_id_number', 120)->nullable();
            $table->string('cvr_contact_number', 60)->nullable();
            $table->string('cvr_address_line', 255)->nullable();
            $table->string('cvr_city', 120)->nullable();
            $table->string('cvr_province', 120)->nullable();
            $table->string('cvr_postal_code', 20)->nullable();
            $table->string('cvr_country', 80)->nullable();
            $table->text('cvr_notes')->nullable();
            $table->string('cvr_id_front_url', 1200);
            $table->string('cvr_id_back_url', 1200)->nullable();
            $table->string('cvr_selfie_url', 1200);
            $table->unsignedBigInteger('cvr_reviewed_by')->nullable();
            $table->text('cvr_review_notes')->nullable();
            $table->timestamp('cvr_reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['cvr_customer_id', 'cvr_status'], 'cvr_customer_status_idx');
            $table->index('cvr_status', 'cvr_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_customer_verification_requests');
    }
};
