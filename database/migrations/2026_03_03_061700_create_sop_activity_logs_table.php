<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('sop_activity_logs', function (Blueprint $table) {
      $table->id();
      $table->foreignId('sop_id')->constrained('sop_documents')->cascadeOnDelete();
      $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
      $table->enum('event_type', ['view', 'open', 'download', 'search_click']);
      $table->string('device')->nullable();
      $table->timestamp('created_at')->useCurrent();

      $table->index(['sop_id', 'created_at']);
      $table->index(['user_id', 'created_at']);
      $table->index(['event_type', 'created_at']);
    });
  }

  public function down(): void {
    Schema::dropIfExists('sop_activity_logs');
  }
};
