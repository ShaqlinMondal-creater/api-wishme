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
            $table->foreign('cover_id')->references('id')->on('t_uploads')->nullOnDelete();
        });

        Schema::table('t_templates', function (Blueprint $table) {
            $table->dropColumn('cover');
        });
    }

    public function down(): void
    {
        Schema::table('t_templates', function (Blueprint $table) {
            $table->string('cover')->nullable()->after('description');
        });

        Schema::table('t_templates', function (Blueprint $table) {
            $table->dropForeign(['cover_id']);
            $table->dropColumn('cover_id');
        });
    }
};
