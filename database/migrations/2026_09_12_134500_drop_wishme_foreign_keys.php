<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropForeignKeys('t_uploads', ['user_id', 'template_id', 'project_id', 'occasion_id']);
        $this->dropForeignKeys('t_projects', ['user_id', 'template_id', 'purchase_id']);
        $this->dropForeignKeys('t_purchases', ['user_id', 'template_id']);
        $this->dropForeignKeys('t_templates', ['occasion_id', 'cover_id']);
        $this->dropForeignKeys('t_occasion', ['thumbnail_id']);
    }

    public function down(): void
    {
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
            if (! Schema::hasColumn($table, $column)) {
                continue;
            }

            try {
                Schema::table($table, function (Blueprint $blueprint) use ($column) {
                    $blueprint->dropForeign([$column]);
                });
            } catch (\Throwable) {
            }
        }
    }
};
