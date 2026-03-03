<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('sop_source_apps', function (Blueprint $table) {
      $table->id();
      $table->string('name')->unique();
      $table->boolean('active')->default(true);
      $table->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists('sop_source_apps');
  }
};
