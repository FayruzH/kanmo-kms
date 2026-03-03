<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('sop_documents', function (Blueprint $table) {
      $table->id();

      $table->string('title');
      $table->foreignId('category_id')->constrained('sop_categories');
      $table->foreignId('department_id')->constrained('sop_departments');

      $table->string('entity')->nullable();

      $table->foreignId('source_app_id')->nullable()->constrained('sop_source_apps')->nullOnDelete();
      $table->string('source_name')->nullable();

      $table->enum('type', ['url', 'file']);
      $table->text('url')->nullable();

      $table->string('file_path')->nullable();
      $table->string('file_mime')->nullable();

      $table->string('version')->nullable();

      $table->date('effective_date')->nullable();
      $table->date('expiry_date');

      $table->foreignId('pic_user_id')->constrained('users');

      $table->enum('status', ['active', 'expiring_soon', 'expired', 'archived'])->default('active');
      $table->timestamp('archived_at')->nullable();

      $table->text('summary')->nullable();

      $table->timestamps();

      // Indexes for filtering/sorting
      $table->index(['status', 'expiry_date']);
      $table->index(['department_id', 'category_id']);
      $table->index(['pic_user_id']);
      $table->index(['updated_at']);

      // Optional: basic keyword search on metadata
      $table->fullText(['title', 'summary']);
    });
  }

  public function down(): void {
    Schema::dropIfExists('sop_documents');
  }
};
