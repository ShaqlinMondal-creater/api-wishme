<?php

use App\Enums\ProjectStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('template_id')->constrained('templates')->restrictOnDelete();
            $table->foreignId('purchase_id')->unique()->constrained('purchases')->restrictOnDelete();
            $table->string('title', 120);
            $table->string('recipient_name', 120);
            $table->string('from_name', 120);
            $table->json('content');
            $table->enum('status', [
                ProjectStatus::Draft->value,
                ProjectStatus::Published->value,
                ProjectStatus::Scheduled->value,
            ])->default(ProjectStatus::Draft->value);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
