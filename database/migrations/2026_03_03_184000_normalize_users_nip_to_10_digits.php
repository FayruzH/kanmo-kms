<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Keep only numeric NIP values in this normalization step.
        DB::statement("UPDATE users SET nip = LPAD(nip, 10, '0') WHERE nip REGEXP '^[0-9]+$' AND CHAR_LENGTH(nip) < 10");
    }

    public function down(): void
    {
        // No-op: normalized value should remain stable.
    }
};

