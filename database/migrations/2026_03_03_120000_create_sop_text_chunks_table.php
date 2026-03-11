<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sop_text_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sop_id')->constrained('sop_documents')->cascadeOnDelete();
            $table->unsignedInteger('chunk_index');
            $table->longText('content_text');
            $table->string('page_ref')->nullable();
            $table->json('embedding')->nullable();
            $table->timestamps();

            $table->index(['sop_id', 'chunk_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sop_text_chunks');
    }
};
