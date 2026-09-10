<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereNotIn('role', [UserRole::Customer->value, UserRole::Admin->value])
            ->update(['role' => UserRole::Customer->value]);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', [UserRole::Customer->value, UserRole::Admin->value])
                ->default(UserRole::Customer->value)
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->default(UserRole::Customer->value)->change();
        });
    }
};
