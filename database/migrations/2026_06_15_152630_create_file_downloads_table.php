<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('file_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('lesson_file_id')->constrained()->cascadeOnDelete();
            $table->integer('download_count')->default(0);
            $table->timestamp('first_download_at')->nullable();
            $table->timestamp('last_download_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'lesson_file_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_downloads');
    }
};
