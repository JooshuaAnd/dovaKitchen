<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_templates', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('code_prefix', 30);
            $table->string('rfm_segment');
            $table->enum('discount_type', ['percentage', 'fixed']);
            $table->decimal('discount_value', 12, 2);
            $table->decimal('min_purchase', 12, 2)->default(0);
            $table->dateTime('valid_until');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_templates');
    }
};
