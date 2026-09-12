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
            $table->index('thumbnail_id');
        });

        Schema::table('templates', function (Blueprint $table) {
            $table->unsignedBigInteger('occasion_id')->nullable()->after('cover');
            $table->string('occasion_slug', 40)->nullable()->after('occasion_id');
            $table->index('occasion_id');
        });

        DB::statement('UPDATE templates SET occasion_slug = occasion');

        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn('occasion');
        });

        $this->dropForeignKeys('uploads', ['user_id', 'template_id', 'project_id']);
        $this->dropForeignKeys('projects', ['user_id', 'template_id', 'purchase_id']);
        $this->dropForeignKeys('purchases', ['user_id', 'template_id']);

        Schema::rename('templates', 't_templates');
        Schema::rename('projects', 't_projects');
        Schema::rename('uploads', 't_uploads');
        Schema::rename('purchases', 't_purchases');

        Schema::table('t_uploads', function (Blueprint $table) {
            $table->unsignedBigInteger('occasion_id')->nullable()->after('project_id');
            $table->index('occasion_id');
        });
    }

    public function down(): void
    {
        Schema::table('t_uploads', function (Blueprint $table) {
            $table->dropIndex(['occasion_id']);
            $table->dropColumn('occasion_id');
        });

        Schema::rename('t_templates', 'templates');
        Schema::rename('t_projects', 'projects');
        Schema::rename('t_uploads', 'uploads');
        Schema::rename('t_purchases', 'purchases');

        Schema::table('templates', function (Blueprint $table) {
            $table->string('occasion', 40)->nullable()->after('cover');
        });

        DB::statement('UPDATE templates SET occasion = occasion_slug');

        Schema::table('templates', function (Blueprint $table) {
            $table->dropIndex(['occasion_id']);
            $table->dropColumn('occasion_id');
            $table->dropColumn('occasion_slug');
        });

        Schema::dropIfExists('t_occasion');
    }

    /**
     * @param list<string> $columns
     */
    private function dropForeignKeys(string $table, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        foreach ($columns as $column) {
            try {
                Schema::table($table, function (Blueprint $blueprint) use ($column) {
                    $blueprint->dropForeign([$column]);
                });
            } catch (\Throwable) {
            }
        }
    }
};
