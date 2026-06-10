<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    if (Schema::hasTable('chatbot_feedback')) {
      return;
    }

    Schema::create('chatbot_feedback', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('user_id')->nullable();
      $table->string('user_name', 120)->nullable();
      $table->string('user_email')->nullable();
      $table->text('question');
      $table->text('detail')->nullable();
      $table->timestamps();

      $table->index(['user_id']);
      $table->index(['created_at']);
      $table->index(['user_email']);
    });
  }

  public function down(): void {
    Schema::dropIfExists('chatbot_feedback');
  }
};
