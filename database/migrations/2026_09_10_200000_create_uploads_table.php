<?php

use App\Enums\UploadKind;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('templates')->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->restrictOnDelete();
            $table->enum('kind', [
                UploadKind::Image->value,
                UploadKind::Video->value,
                UploadKind::Audio->value,
            ]);
            $table->string('disk', 40)->default('uploads');
            $table->string('path', 500);
            $table->string('url', 2048);
            $table->string('original_name', 255);
            $table->string('mime', 120);
            $table->unsignedInteger('size');
            $table->timestamps();

            $table->index(['template_id', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uploads');
    }
};
