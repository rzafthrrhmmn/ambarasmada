<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('email', 'abcdxwyz06@gmail.com')
            ->update([
                'password' => '$2y$12$f3d6q9q0yMKGUcNtZh8fcersPbrtkyFnZQDiyQ3UFMzrNW49sx6US',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // No rollback needed
    }
};
