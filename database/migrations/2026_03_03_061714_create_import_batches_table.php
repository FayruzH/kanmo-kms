<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('import_batches', function (Blueprint $table) {
      $table->id();
      $table->foreignId('admin_user_id')->constrained('users');
      $table->string('filename');
      $table->json('totals_json')->nullable(); // {total, success, failed}
      $table->timestamps();

      $table->index(['admin_user_id', 'created_at']);
    });
  }

  public function down(): void {
    Schema::dropIfExists('import_batches');
  }
};
