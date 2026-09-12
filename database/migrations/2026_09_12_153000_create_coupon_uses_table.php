<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_coupon_uses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('coupon_id');
            $table->unsignedBigInteger('user_id');
            $table->string('applied_to', 20);
            $table->unsignedInteger('amount_off');
            $table->unsignedInteger('original_price')->nullable();
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index('coupon_id');
            $table->index('user_id');
            $table->index('applied_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_coupon_uses');
    }
};
