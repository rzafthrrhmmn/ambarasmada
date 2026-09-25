<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('email', 'abcdwxyz06@gmail.com')
            ->update([
                'password' => '$2y$12$1kjaSOgu/X168SomqYiVXuYzR/LLH7sxrr02mX1X/Tgv5Qme2H2C2',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // No rollback needed
    }
};