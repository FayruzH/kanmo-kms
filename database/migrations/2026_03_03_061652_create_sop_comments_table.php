<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('sop_comments', function (Blueprint $table) {
      $table->id();
      $table->foreignId('sop_id')->constrained('sop_documents')->cascadeOnDelete();
      $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
      $table->text('comment_text');
      $table->timestamps();
      $table->timestamp('deleted_at')->nullable();

      $table->index(['sop_id', 'created_at']);
      $table->index(['user_id', 'created_at']);
    });
  }

  public function down(): void {
    Schema::dropIfExists('sop_comments');
  }
};
