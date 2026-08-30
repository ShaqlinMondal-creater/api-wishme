<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('mobile_no', 20)->nullable()->unique();
            $table->string('role', 30)->default('customer')->index();
            $table->string('otp')->nullable();
            $table->timestamp('otp_expire')->nullable();
            $table->timestamp('mobile_verify_at')->nullable();
            $table->string('google_id')->nullable()->unique();
            $table->string('auth_provider', 30)->default('email');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_loggedin')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->date('dob')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['mobile_no']);
            $table->dropUnique(['google_id']);
            $table->dropIndex(['role']);
            $table->dropColumn([
                'mobile_no',
                'role',
                'otp',
                'otp_expire',
                'mobile_verify_at',
                'google_id',
                'auth_provider',
                'is_active',
                'is_loggedin',
                'is_deleted',
                'dob',
            ]);
        });
    }
};
