<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('sop_document_tag', function (Blueprint $table) {
      $table->id();
      $table->foreignId('sop_document_id')->constrained('sop_documents')->cascadeOnDelete();
      $table->foreignId('sop_tag_id')->constrained('sop_tags')->cascadeOnDelete();
      $table->timestamps();

      $table->unique(['sop_document_id', 'sop_tag_id']);
      $table->index(['sop_document_id']);
    });
  }

  public function down(): void {
    Schema::dropIfExists('sop_document_tag');
  }
};
