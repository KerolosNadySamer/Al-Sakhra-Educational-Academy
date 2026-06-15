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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained('organizations')->nullOnDelete();
            $table->string('phone')->nullable()->after('email');
            $table->enum('role', [
                'super_admin',
                'center_owner',
                'center_admin',
                'teacher',
                'teacher_assistant',
                'cashier',
                'student',
            ])->default('student')->after('password');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->after('role');

            $table->index(['organization_id', 'role']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropIndex(['organization_id', 'role']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'organization_id',
                'phone',
                'role',
                'status',
            ]);
        });
    }
};
