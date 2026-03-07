<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbl_admin_notifications')) {
            Schema::create('tbl_admin_notifications', function (Blueprint $table) {
                $table->bigIncrements('an_id');
                $table->string('an_type', 80)->default('system');
                $table->string('an_severity', 20)->default('info');
                $table->string('an_title', 180);
                $table->string('an_message', 500)->nullable();
                $table->string('an_href', 255)->nullable();
                $table->json('an_payload')->nullable();
                $table->string('an_source_type', 80)->nullable();
                $table->unsignedBigInteger('an_source_id')->nullable();
                $table->timestamp('an_created_at')->nullable();

                $table->index('an_type', 'idx_an_type');
                $table->index('an_created_at', 'idx_an_created_at');
                $table->index(['an_source_type', 'an_source_id'], 'idx_an_source');
                $table->unique(['an_type', 'an_source_type', 'an_source_id'], 'uniq_an_type_source');
            });
        }

        if (!Schema::hasTable('tbl_admin_notification_reads')) {
            Schema::create('tbl_admin_notification_reads', function (Blueprint $table) {
                $table->bigIncrements('anr_id');
                $table->unsignedBigInteger('anr_notification_id');
                $table->unsignedBigInteger('anr_admin_id');
                $table->timestamp('anr_read_at')->nullable();

                $table->unique(['anr_notification_id', 'anr_admin_id'], 'uniq_anr_notification_admin');
                $table->index('anr_admin_id', 'idx_anr_admin');
                $table->index('anr_read_at', 'idx_anr_read_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_admin_notification_reads');
        Schema::dropIfExists('tbl_admin_notifications');
    }
};

