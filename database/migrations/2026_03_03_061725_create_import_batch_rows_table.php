<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('import_batch_rows', function (Blueprint $table) {
      $table->id();
      $table->foreignId('batch_id')->constrained('import_batches')->cascadeOnDelete();
      $table->unsignedInteger('row_number');
      $table->enum('status', ['success', 'failed']);
      $table->text('error_message')->nullable();
      $table->json('raw_json')->nullable();
      $table->timestamps();

      $table->index(['batch_id', 'status']);
    });
  }

  public function down(): void {
    Schema::dropIfExists('import_batch_rows');
  }
};  
