<?php

use App\Enums\PurchaseStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('template_id')->constrained('templates')->restrictOnDelete();
            $table->unsignedInteger('price');
            $table->enum('status', [PurchaseStatus::Paid->value])->default(PurchaseStatus::Paid->value);
            $table->timestamps();

            $table->unique(['user_id', 'template_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
