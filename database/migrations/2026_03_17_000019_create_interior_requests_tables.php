<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbl_interior_requests')) {
            Schema::create('tbl_interior_requests', function (Blueprint $table) {
                $table->bigIncrements('ir_id');
                $table->unsignedBigInteger('ir_customer_id');
                $table->string('ir_reference', 40)->unique();
                $table->string('ir_service_type', 120);
                $table->string('ir_project_type', 120)->nullable();
                $table->string('ir_property_type', 120)->nullable();
                $table->string('ir_project_scope', 180)->nullable();
                $table->string('ir_budget', 120)->nullable();
                $table->string('ir_style_preference', 160)->nullable();
                $table->date('ir_preferred_date')->nullable();
                $table->string('ir_preferred_time', 60)->nullable();
                $table->string('ir_flexibility', 120)->nullable();
                $table->string('ir_target_timeline', 120)->nullable();
                $table->string('ir_first_name', 120);
                $table->string('ir_last_name', 120);
                $table->string('ir_email', 255);
                $table->string('ir_phone', 50)->nullable();
                $table->text('ir_notes')->nullable();
                $table->string('ir_referral', 120)->nullable();
                $table->json('ir_inspiration_files')->nullable();
                $table->string('ir_status', 40)->default('pending');
                $table->string('ir_priority', 40)->default('normal');
                $table->unsignedBigInteger('ir_assigned_admin_id')->nullable();
                $table->timestamps();

                $table->index('ir_customer_id', 'idx_ir_customer');
                $table->index('ir_status', 'idx_ir_status');
                $table->index('ir_assigned_admin_id', 'idx_ir_assigned_admin');
                $table->index('created_at', 'idx_ir_created_at');
            });
        }

        if (!Schema::hasTable('tbl_interior_request_updates')) {
            Schema::create('tbl_interior_request_updates', function (Blueprint $table) {
                $table->bigIncrements('iru_id');
                $table->unsignedBigInteger('iru_request_id');
                $table->unsignedBigInteger('iru_actor_admin_id')->nullable();
                $table->string('iru_type', 40)->default('message');
                $table->string('iru_title', 180);
                $table->text('iru_message');
                $table->json('iru_payload')->nullable();
                $table->boolean('iru_visible_to_customer')->default(true);
                $table->timestamps();

                $table->index('iru_request_id', 'idx_iru_request');
                $table->index('iru_type', 'idx_iru_type');
                $table->index('created_at', 'idx_iru_created_at');
            });
        }

        if (!Schema::hasTable('tbl_customer_notifications')) {
            Schema::create('tbl_customer_notifications', function (Blueprint $table) {
                $table->bigIncrements('cn_id');
                $table->unsignedBigInteger('cn_customer_id');
                $table->string('cn_type', 80)->default('system');
                $table->string('cn_severity', 20)->default('info');
                $table->string('cn_title', 180);
                $table->string('cn_message', 500)->nullable();
                $table->string('cn_href', 255)->nullable();
                $table->json('cn_payload')->nullable();
                $table->string('cn_source_type', 80)->nullable();
                $table->unsignedBigInteger('cn_source_id')->nullable();
                $table->timestamp('cn_created_at')->nullable();

                $table->index('cn_customer_id', 'idx_cn_customer');
                $table->index('cn_created_at', 'idx_cn_created_at');
                $table->index(['cn_source_type', 'cn_source_id'], 'idx_cn_source');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_customer_notifications');
        Schema::dropIfExists('tbl_interior_request_updates');
        Schema::dropIfExists('tbl_interior_requests');
    }
};
