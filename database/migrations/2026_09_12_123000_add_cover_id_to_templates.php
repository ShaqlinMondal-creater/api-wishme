<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('t_templates', function (Blueprint $table) {
            $table->unsignedBigInteger('cover_id')->nullable()->after('cover');
            $table->index('cover_id');
        });

        if (Schema::hasColumn('t_templates', 'cover')) {
            Schema::table('t_templates', function (Blueprint $table) {
                $table->dropColumn('cover');
            });
        }
    }

    public function down(): void
    {
        Schema::table('t_templates', function (Blueprint $table) {
            $table->string('cover')->nullable()->after('description');
        });

        Schema::table('t_templates', function (Blueprint $table) {
            $table->dropIndex(['cover_id']);
            $table->dropColumn('cover_id');
        });
    }
};
