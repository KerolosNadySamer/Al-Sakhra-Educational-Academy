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
        Schema::table('lesson_files', function (Blueprint $table) {
            $table->integer('max_downloads')->default(1)->after('allow_download');
            $table->integer('download_expiry_days')->default(30)->after('max_downloads');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lesson_files', function (Blueprint $table) {
            $table->dropColumn(['max_downloads', 'download_expiry_days']);
        });
    }
};
