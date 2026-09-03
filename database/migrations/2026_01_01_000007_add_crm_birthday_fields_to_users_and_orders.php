<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('name');
            }
            if (!Schema::hasColumn('users', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'birthday_reward_claimed_year')) {
                $table->integer('birthday_reward_claimed_year')->nullable()->after('date_of_birth');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'subtotal_amount')) {
                $table->decimal('subtotal_amount', 12, 2)->nullable()->after('order_code');
            }
            if (!Schema::hasColumn('orders', 'discount_amount')) {
                $table->decimal('discount_amount', 12, 2)->default(0)->after('subtotal_amount');
            }
            if (!Schema::hasColumn('orders', 'discount_type')) {
                $table->string('discount_type')->nullable()->after('discount_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('users', 'phone')) $columns[] = 'phone';
            if (Schema::hasColumn('users', 'date_of_birth')) $columns[] = 'date_of_birth';
            if (Schema::hasColumn('users', 'birthday_reward_claimed_year')) $columns[] = 'birthday_reward_claimed_year';
            if (!empty($columns)) $table->dropColumn($columns);
        });

        Schema::table('orders', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('orders', 'subtotal_amount')) $columns[] = 'subtotal_amount';
            if (Schema::hasColumn('orders', 'discount_amount')) $columns[] = 'discount_amount';
            if (Schema::hasColumn('orders', 'discount_type')) $columns[] = 'discount_type';
            if (!empty($columns)) $table->dropColumn($columns);
        });
    }
};
