<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $seenPhones = [];

        DB::table('users')->select('id', 'phone')->whereNotNull('phone')->orderBy('id')->each(function ($user) use (&$seenPhones) {
            $phone = preg_replace('/[^0-9]/', '', (string) $user->phone);

            if (str_starts_with($phone, '0')) {
                $phone = '62' . substr($phone, 1);
            } elseif (str_starts_with($phone, '8')) {
                $phone = '62' . $phone;
            }

            if ($phone === '') {
                DB::table('users')->where('id', $user->id)->update(['phone' => null]);
                return;
            }

            if (isset($seenPhones[$phone])) {
                throw new RuntimeException("Duplicate customer phone after normalization: {$phone}");
            }

            $seenPhones[$phone] = true;
            DB::table('users')->where('id', $user->id)->update(['phone' => $phone]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();
            $table->unique('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_phone_unique');
            $table->string('email')->nullable(false)->change();
            $table->string('password')->nullable(false)->change();
        });
    }
};
