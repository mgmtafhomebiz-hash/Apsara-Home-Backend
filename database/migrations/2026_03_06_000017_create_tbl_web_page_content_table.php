<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_web_page_content')) {
            return;
        }

        Schema::create('tbl_web_page_content', function (Blueprint $table) {
            $table->bigIncrements('wpc_id');
            $table->string('wpc_type', 30)->index();
            $table->string('wpc_key', 120)->nullable();
            $table->string('wpc_title', 255)->nullable();
            $table->string('wpc_subtitle', 255)->nullable();
            $table->text('wpc_body')->nullable();
            $table->string('wpc_image_url', 1200)->nullable();
            $table->string('wpc_link_url', 1200)->nullable();
            $table->string('wpc_button_text', 120)->nullable();
            $table->json('wpc_payload')->nullable();
            $table->integer('wpc_sort')->default(0)->index();
            $table->boolean('wpc_status')->default(true)->index();
            $table->timestamp('wpc_start_at')->nullable()->index();
            $table->timestamp('wpc_end_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_web_page_content');
    }
};

