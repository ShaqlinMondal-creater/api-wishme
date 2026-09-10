<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('name', 120);
            $table->text('description');
            $table->string('cover');
            $table->string('occasion', 40);
            $table->unsignedInteger('price');
            $table->boolean('has_letter')->default(false);
            $table->boolean('has_stories')->default(false);
            $table->boolean('has_moments')->default(false);
            $table->boolean('has_privacy')->default(false);
            $table->boolean('has_surprise_gift')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
