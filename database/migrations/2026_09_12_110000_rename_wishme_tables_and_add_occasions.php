<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_occasion', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->text('description');
            $table->string('type', 40);
            $table->unsignedBigInteger('thumbnail_id')->nullable();
            $table->timestamps();

            $table->index('type');
        });

        Schema::table('templates', function (Blueprint $table) {
            $table->unsignedBigInteger('occasion_id')->nullable()->after('cover');
            $table->string('occasion_slug', 40)->nullable()->after('occasion_id');
        });

        DB::statement('UPDATE templates SET occasion_slug = occasion');

        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn('occasion');
        });

        Schema::table('uploads', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['template_id']);
            $table->dropForeign(['project_id']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['template_id']);
            $table->dropForeign(['purchase_id']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['template_id']);
        });

        Schema::rename('templates', 't_templates');
        Schema::rename('projects', 't_projects');
        Schema::rename('uploads', 't_uploads');
        Schema::rename('purchases', 't_purchases');

        Schema::table('t_uploads', function (Blueprint $table) {
            $table->foreignId('occasion_id')->nullable()->after('project_id')->constrained('t_occasion')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('template_id')->references('id')->on('t_templates')->restrictOnDelete();
            $table->foreign('project_id')->references('id')->on('t_projects')->restrictOnDelete();
        });

        Schema::table('t_projects', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('template_id')->references('id')->on('t_templates')->restrictOnDelete();
            $table->foreign('purchase_id')->references('id')->on('t_purchases')->restrictOnDelete();
        });

        Schema::table('t_purchases', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('template_id')->references('id')->on('t_templates')->restrictOnDelete();
        });

        Schema::table('t_templates', function (Blueprint $table) {
            $table->foreign('occasion_id')->references('id')->on('t_occasion')->nullOnDelete();
        });

        Schema::table('t_occasion', function (Blueprint $table) {
            $table->foreign('thumbnail_id')->references('id')->on('t_uploads')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('t_occasion', function (Blueprint $table) {
            $table->dropForeign(['thumbnail_id']);
        });

        Schema::table('t_templates', function (Blueprint $table) {
            $table->dropForeign(['occasion_id']);
        });

        Schema::table('t_purchases', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['template_id']);
        });

        Schema::table('t_projects', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['template_id']);
            $table->dropForeign(['purchase_id']);
        });

        Schema::table('t_uploads', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['template_id']);
            $table->dropForeign(['project_id']);
            $table->dropForeign(['occasion_id']);
            $table->dropColumn('occasion_id');
        });

        Schema::rename('t_templates', 'templates');
        Schema::rename('t_projects', 'projects');
        Schema::rename('t_uploads', 'uploads');
        Schema::rename('t_purchases', 'purchases');

        Schema::table('uploads', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('template_id')->references('id')->on('templates')->restrictOnDelete();
            $table->foreign('project_id')->references('id')->on('projects')->restrictOnDelete();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('template_id')->references('id')->on('templates')->restrictOnDelete();
            $table->foreign('purchase_id')->references('id')->on('purchases')->restrictOnDelete();
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('template_id')->references('id')->on('templates')->restrictOnDelete();
        });

        Schema::table('templates', function (Blueprint $table) {
            $table->string('occasion', 40)->nullable()->after('cover');
        });

        DB::statement('UPDATE templates SET occasion = occasion_slug');

        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn('occasion_id');
            $table->dropColumn('occasion_slug');
        });

        Schema::dropIfExists('t_occasion');
    }
};
