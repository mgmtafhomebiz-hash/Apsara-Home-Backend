<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // No-op: duplicate of 2026_02_19_051915_create_personal_access_tokens_table
    }

    public function down(): void
    {
        // No-op: original migration handles dropIfExists
    }
};
