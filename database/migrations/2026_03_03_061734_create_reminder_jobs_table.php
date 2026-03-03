<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('reminder_jobs', function (Blueprint $table) {
      $table->id();
      $table->foreignId('sop_id')->constrained('sop_documents')->cascadeOnDelete();
      $table->foreignId('pic_user_id')->constrained('users')->cascadeOnDelete();
      $table->enum('reminder_type', ['expiring', 'expired']);
      $table->timestamp('sent_at')->nullable();
      $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
      $table->json('meta_json')->nullable();
      $table->timestamps();

      $table->index(['status', 'reminder_type']);
      $table->index(['pic_user_id', 'created_at']);
    });
  }

  public function down(): void {
    Schema::dropIfExists('reminder_jobs');
  }
};
