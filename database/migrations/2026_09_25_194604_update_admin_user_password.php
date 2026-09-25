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
                'password' => '$2y$12$.DLeu50/7tBGheE2.Gm0numhvrV7H4cCc6ehBFES2ojAT3xV/RX3K',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // No rollback needed
    }
};