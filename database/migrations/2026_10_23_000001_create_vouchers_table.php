<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('discount_value', 12, 2);
            $table->decimal('min_purchase', 12, 2)->default(0);
            $table->dateTime('valid_until');
            $table->string('rfm_segment')->nullable();
            $table->string('title')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_used')->default(false);
            $table->dateTime('used_at')->nullable();
            $table->foreignId('used_in_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
