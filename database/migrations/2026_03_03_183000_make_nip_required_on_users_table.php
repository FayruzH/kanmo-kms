<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fill legacy empty NIP with deterministic unique fallback before making column NOT NULL.
        DB::statement("UPDATE users SET nip = CONCAT('TMP', id) WHERE nip IS NULL OR nip = ''");
        DB::statement("ALTER TABLE users MODIFY nip VARCHAR(255) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY nip VARCHAR(255) NULL");
    }
};

