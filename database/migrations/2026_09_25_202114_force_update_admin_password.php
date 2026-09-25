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
                'password' => '$2y$12$Gd7xPA6sBDDyQSx.9.cZ5e.Jj9cGQwcbhu6D2ncN4ednq5PfgSrEW',
                'status' => 'approved',
                'email_verified_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // No rollback needed
    }
};